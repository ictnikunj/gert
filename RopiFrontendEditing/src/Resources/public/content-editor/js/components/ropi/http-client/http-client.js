import HttpRequest from '../http-message/http-request.js?v=1637255330';
import HttpResponse from '../http-message/http-response.js?v=1637255330';
import HttpError from '../http-message/http-error.js?v=1637255330';
import TypeUtil from '../type-util/type-util.js?v=1637255330';
import StringUtil from '../string-util/string-util.js?v=1637255330';
import QueryString from '../query-string/query-string.js?v=1637255330';

export default class HttpClient {

  constructor() {
    this._requestMap = {};
  }

  send(httpRequest) {
    return this._transfer(httpRequest);
  }

  request(uri, method) {
    return this.send(
      (new HttpRequest(uri)).setMethod(method)
    );
  }

  abort(httpRequest) {
    if (this._requestMap[httpRequest]) {
      this._requestMap[httpRequest]._ropiHttpClientAborted = true;
      this._requestMap[httpRequest].abort();
      delete this._requestMap[httpRequest];
      return true;
    }

    return false;
  }

  setAuthentication(authentication) {
    if (!TypeUtil.isFunction(authentication.authenticateFor)) {
      throw new TypeError(
        'Argument authentication has to be an object which implements authenticateFor() method'
      );
    }

    this._authentication = authentication;

    return this;
  }

  getAuthentication() {
    return this._authentication ? this._authentication : null;
  }

  _transfer(httpRequest) {
    if (!(httpRequest instanceof HttpRequest)) {
       throw new TypeError(
         'Argument httpRequest must be an instance of HttpRequest'
       );
    }

    let authentication = this.getAuthentication();
    if (authentication) {
      return authentication.authenticateFor(httpRequest).then(() => {
          return this._transferRequest(httpRequest);
      });
    }

    return this._transferRequest(httpRequest);
  }

  _transferRequest(httpRequest) {
    return new Promise((resolve, reject) => {
      let httpResponse = new HttpResponse();
      let xmlHttpRequest = new XMLHttpRequest();

      this._requestMap[httpRequest] = xmlHttpRequest;

      //xmlHttpRequest.responseType = 'arraybuffer';
      xmlHttpRequest.responseType = 'text';

      xmlHttpRequest.onreadystatechange = () => {
        if (xmlHttpRequest.readyState === XMLHttpRequest.HEADERS_RECEIVED) {
          httpResponse.setStatusCode(xmlHttpRequest.status);
          httpResponse.setReasonPhrase(xmlHttpRequest.statusText);

          httpResponse.setHeaders(
            this._parseResponseHeader(
              xmlHttpRequest.getAllResponseHeaders()
            )
          );

          return;
        }

        if (xmlHttpRequest.readyState === XMLHttpRequest.DONE) {
          if (this._requestMap[httpRequest]._ropiHttpClientAborted) {
            return;
          }

          delete this._requestMap[httpRequest];

          httpResponse.setBody(xmlHttpRequest.responseText);

          if (xmlHttpRequest.status >= 200 && xmlHttpRequest.status <= 299) {
            resolve(httpResponse);
          } else {
            reject(new HttpError(
              xmlHttpRequest.statusText,
              httpRequest,
              httpResponse
            ));
          }

          return;
        }
      };

      xmlHttpRequest.onerror = () => {
        delete this._requestMap[httpRequest];

        reject(new HttpError(
          'Network Error',
          httpRequest,
          httpResponse
        ));
      };

      let uri = httpRequest.getUri();

      let queryParameters = httpRequest.getQueryParameters();
      if (queryParameters) {
        uri = this._appendQueryStringToUri(
          uri,
          this._buildQueryString(queryParameters)
        );
      }

      xmlHttpRequest.open(httpRequest.getMethod(), uri, true);

      for (let headerName of Object.keys(httpRequest.getHeaders())) {
        xmlHttpRequest.setRequestHeader(
          headerName,
          httpRequest.getHeader(headerName)
        );
      }

      xmlHttpRequest.send(httpRequest.getBody());
    });
  }

  _buildQueryString(queryParameters) {
    return QueryString.stringify(queryParameters);
  }

  _appendQueryStringToUri(uri, queryString) {
    if (queryString) {
      if (uri.indexOf('?') === -1) {
        uri += '?';
      } else if (StringUtil.lastChar(uri.trim()) !== '&') {
        uri += '&';
      }

      uri += queryString;
    }

    return uri;
  }

  _parseResponseHeader(responseHeader) {
    let parsedHeaders = {};

    let headerLines = responseHeader.trim().split(/[\r\n]+/);
    for (let headerLine of headerLines) {
      let [headerName, headerValue] = headerLine.split(':', 2);

      parsedHeaders[headerName.trim()] = headerValue ? headerValue.trim() : '';
    }

    return parsedHeaders;
  }
}

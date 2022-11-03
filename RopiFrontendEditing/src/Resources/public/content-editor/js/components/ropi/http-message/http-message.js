import TypeUtil from '../type-util/type-util.js?v=1637255330';
import StringUtil from '../string-util/string-util.js?v=1637255330';

export default class HttpMessage {

  constructor() {
    this._headers = {};
    this._body = '';
  }

  setHeaders(headers) {
    this._headers = {};

    if (TypeUtil.isTraversable(headers)) {
      for (let headerName of Object.keys(headers)) {
        let headerValue = headers[headerName];

        this.setHeader(headerName, headerValue);
      }
    }

    return this;
  }

  setHeader(headerName, headerValue) {
    headerValue = headerValue == null ? '' : headerValue;

    this._headers[this._normalizeHeaderName(headerName)] = String(headerValue).trim();

    return this;
  }

  getHeaders() {
    return this._headers;
  }

  getHeader(headerName) {
    headerName = this._normalizeHeaderName(headerName);
    return this._headers[headerName] == null
            ? ''
            : this._headers[headerName];
  }

  removeHeader(headerName) {
    delete this._headers[this._normalizeHeaderName(headerName)];

    return this;
  }

  setBody(body) {
    this._body = body == null ? '' : body;

    return this;
  }

  getBody() {
    return this._body;
  }

  _normalizeHeaderName(headerName) {
    let parts = String(headerName).trim().toLowerCase().split('-');
    let normalizedParts = [];

    for (let part of parts) {
      normalizedParts.push(StringUtil.capitalize(part.trim()));
    }

    return normalizedParts.join('-');
  }

  toString(withHeader) {
    let header = '';

    if (withHeader) {
      for (let headerName of Object.keys(this._headers)) {
        let headerValue = this._headers[headerName];
        header += headerName + ': ' + headerValue + '\r\n';
      }

      header += '\r\n';
    }

    return header + this.getBody();
  }
}

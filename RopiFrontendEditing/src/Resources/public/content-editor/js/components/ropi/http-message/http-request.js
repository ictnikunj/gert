import TypeUtil from '../type-util/type-util.js?v=1637255330';
import HttpMessage from './http-message.js?v=1637255330';

export default class HttpRequest extends HttpMessage {

  static get METHOD_CONNECT() { return 'CONNECT'; }
  static get METHOD_DELETE() { return 'DELETE'; }
  static get METHOD_GET() { return 'GET'; }
  static get METHOD_HEAD() { return 'HEAD'; }
  static get METHOD_OPTIONS() { return 'OPTIONS'; }
  static get METHOD_PATCH() { return 'PATCH'; }
  static get METHOD_POST() { return 'POST'; }
  static get METHOD_PUT() { return 'PUT'; }
  static get METHOD_TRACE() { return 'TRACE'; }

  constructor(uri) {
    super();

    this.setUri(uri);
    this.setMethod(HttpRequest.METHOD_GET);

    this._queryParameters = {};
  }

  setUri(uri) {
    this._uri = uri == null ? '' : String(uri);

    return this;
  }

  getUri() {
    return this._uri;
  }

  setQueryParameters(queryParameters) {
    if (TypeUtil.isObject(queryParameters)) {
      this._queryParameters = queryParameters;
    } else {
      throw new TypeError('Argument queryParameters has to be an object');
    }

    return this;
  }

  getQueryParameters(queryParameters) {
    return this._queryParameters;
  }

  setQueryParameter(queryParameterName, queryParameterValue) {
    this._queryParameters[queryParameterName] = queryParameterValue;

    return this;
  }

  removeQueryParameter(queryParameterName, queryParameterValue) {
    this._queryParameters[queryParameterName] = queryParameterValue;

    return this;
  }

  setMethod(method) {
    if (method == null) {
      method = HttpRequest.METHOD_GET;
    }

    this._method = String(method).toUpperCase();

    return this;
  }

  getMethod() {
    return this._method;
  }
}

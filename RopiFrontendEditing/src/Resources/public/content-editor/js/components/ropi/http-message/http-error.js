export default class HttpError extends Error {

  constructor(message, httpRequest, httpResponse) {
    super(message);

    this._httpRequest = httpRequest;
    this._httpResponse = httpResponse;
  }

  getHttpRequest() {
    return this._httpRequest;
  }

  getHttpResponse() {
    return this._httpResponse;
  }
}

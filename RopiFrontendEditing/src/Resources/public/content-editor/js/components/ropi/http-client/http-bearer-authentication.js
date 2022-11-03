import HttpRequest from '../http-message/http-request.js?v=1637255330';

export default class HttpBearerAuthentication {

  constructor(token) {
    this.setToken(token);
  }

  setToken(token) {
    this._token = token == null ? '' : String(token);

    return this;
  }

  getToken() {
    return this._token;
  }

  authenticateFor(httpRequest) {
    if (!(httpRequest instanceof HttpRequest)) {
       throw new TypeError(
         'Argument httpRequest must be an instance of HttpRequest'
       );
    }

    return new Promise((resolve, reject) => {
      httpRequest.setHeader(
        'Authorization',
        'Bearer ' + this.getToken()
      );

      resolve();
    });
  }
}

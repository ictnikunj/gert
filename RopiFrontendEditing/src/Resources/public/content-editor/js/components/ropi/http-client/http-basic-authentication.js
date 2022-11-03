import HttpRequest from '../http-message/http-request.js?v=1637255330';

export default class HttpBasicAuthentication {

  constructor(username, password) {
    this.setUsername(username);
    this.setPassword(password);
  }

  setUsername(username) {
    this._username = username == null ? '' : String(username);

    return this;
  }

  getUsername() {
    return this._username;
  }

  setPassword(password) {
    this._password = password == null ? '' : String(password);

    return this;
  }

  getPassword() {
    return this._password;
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
        'Basic ' + btoa(this.getUsername() + ':' + this.getPassword())
      );

      resolve();
    });
  }
}

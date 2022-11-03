import HttpRequest from '../http-message/http-request.js?v=1637255330';
import TypeUtil from '../type-util/type-util.js?v=1637255330';

export default new class {

    constructor() {
        this._bearerAuth = {
            expiry: 0,
            access: '',
            refresh: ''
        };

        this.loadBearerAuth();
    }

    get bearerAuth() {
        return this._bearerAuth;
    }

    loadBearerAuth() {
        try {
            let bearerAuth = JSON.parse(localStorage.getItem('ropiFrontendEditingBearerAuth'));
            if (TypeUtil.isObject(bearerAuth)) {
                this._bearerAuth = bearerAuth;
            }
        } catch (e) {
            // Fail silently
        }
    }

    clearBearerAuth() {
        localStorage.removeItem('ropiFrontendEditingBearerAuth');
    }

    persistBearerAuth() {
        localStorage.setItem('ropiFrontendEditingBearerAuth', JSON.stringify(this.bearerAuth));
    }

    createAuthenticatedRequest(url) {
        let request = new HttpRequest(url);
        request.setHeader('Content-Type', 'application/json');
        request.setHeader('Accept', 'application/json');

        if (!this.bearerAuth.access) {
            return request;
        }

        this.setAuthorizationHeaderForRequest(request);

        return request;
    }

    setAuthorizationHeaderForRequest(request) {
        request.setHeader('Authorization', 'Bearer ' + this.bearerAuth.access);
    }
}

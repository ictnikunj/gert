import HttpMessage from './http-message.js?v=1637255330';

export default class HttpResponse extends HttpMessage {

  static get STATUS_CONTINUE() { return 100; }
  static get STATUS_SWITCHING_PROTOCOLS() { return 101; }
  static get STATUS_PROCESSING() { return 102; }
  static get STATUS_OK() { return 200; }
  static get STATUS_CREATED() { return 201; }
  static get STATUS_ACCEPTED() { return 202; }
  static get STATUS_NON_AUTHORITATIVE_INFORMATION() { return 203; }
  static get STATUS_NO_CONTENT() { return 204; }
  static get STATUS_RESET_CONTENT() { return 205; }
  static get STATUS_PARTIAL_CONTENT() { return 206; }
  static get STATUS_MULTI_STATUS() { return 207; }
  static get STATUS_ALREADY_REPORTED() { return 208; }
  static get STATUS_IM_USED() { return 226; }
  static get STATUS_MULTIPLE_CHOICES() { return 300; }
  static get STATUS_MOVED_PERMANENTLY() { return 301; }
  static get STATUS_FOUND_MOVED_TEMPORARILY() { return 302; }
  static get STATUS_SEE_OTHER() { return 303; }
  static get STATUS_NOT_MODIFIED() { return 304; }
  static get STATUS_USE_PROXY() { return 305; }
  static get STATUS_TEMPORARY_REDIRECT() { return 307; }
  static get STATUS_PERMANENT_REDIRECT() { return 308; }
  static get STATUS_BAD_REQUEST() { return 400; }
  static get STATUS_UNAUTHORIZED() { return 401; }
  static get STATUS_PAYMENT_REQUIRED() { return 402; }
  static get STATUS_FORBIDDEN() { return 403; }
  static get STATUS_NOT_FOUND() { return 404; }
  static get STATUS_METHOD_NOT_ALLOWED() { return 405; }
  static get STATUS_NOT_ACCEPTABLE() { return 406; }
  static get STATUS_PROXY_AUTHENTICATION_REQUIRED() { return 407; }
  static get STATUS_REQUEST_TIME_OUT() { return 408; }
  static get STATUS_CONFLICT() { return 409; }
  static get STATUS_GONE() { return 410; }
  static get STATUS_LENGTH_REQUIRED() { return 411; }
  static get STATUS_PRECONDITION_FAILED() { return 412; }
  static get STATUS_REQUEST_ENTITY_TOO_LARGE() { return 413; }
  static get STATUS_REQUEST_URL_TOO_LONG() { return 414; }
  static get STATUS_UNSUPPORTED_MEDIA_TYPE() { return 415; }
  static get STATUS_REQUESTED_RANGE_NOT_SATISFIABLE() { return 416; }
  static get STATUS_EXPECTATION_FAILED() { return 417; }
  static get STATUS_I_AM_A_TEAPOT() { return 418; }
  static get STATUS_POLICY_NOT_FULFILLED() { return 420; }
  static get STATUS_MISDIRECTED_REQUEST() { return 421; }
  static get STATUS_UNPROCESSABLE_ENTITY() { return 422; }
  static get STATUS_LOCKED() { return 423; }
  static get STATUS_FAILED_DEPENDENCY() { return 424; }
  static get STATUS_UNORDERED_COLLECTION() { return 425; }
  static get STATUS_UPGRADE_REQUIRED() { return 426; }
  static get STATUS_PRECONDITION_REQUIRED() { return 428; }
  static get STATUS_TOO_MANY_REQUESTS() { return 429; }
  static get STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE() { return 431; }
  static get STATUS_UNAVAILABLE_FOR_LEGAL_REASONS() { return 451; }
  static get STATUS_INTERNAL_SERVER_ERROR() { return 500; }
  static get STATUS_NOT_IMPLEMENTED() { return 501; }
  static get STATUS_BAD_GATEWAY() { return 502; }
  static get STATUS_SERVICE_UNAVAILABLE() { return 503; }
  static get STATUS_GATEWAY_TIME_OUT() { return 504; }
  static get STATUS_HTTP_VERSION_NOT_SUPPORTED() { return 505; }
  static get STATUS_VARIANT_ALSO_NEGOTIATES() { return 506; }
  static get STATUS_INSUFFICIENT_STORAGE() { return 507; }
  static get STATUS_LOOP_DETECTED() { return 508; }
  static get STATUS_BANDWIDTH_LIMIT_EXCEEDED() { return 509; }
  static get STATUS_NOT_EXTENDED() { return 510; }
  static get STATUS_NETWORK_AUTHENTICATION_REQUIRED() { return 511; }

  constructor(statusCode, reasonPhrase) {
    super();

    this.setStatusCode(statusCode);
    this.setReasonPhrase(reasonPhrase);
  }

  setStatusCode(statusCode) {
    statusCode = parseInt(statusCode, 10);
    this._statusCode = statusCode ? statusCode : 0;

    return this;
  }

  getStatusCode() {
    return this._statusCode;
  }

  setReasonPhrase(reasonPhrase) {
    this._reasonPhrase = reasonPhrase == null ? '' : String(reasonPhrase);

    return this;
  }

  getReasonPhrase() {
    return this._reasonPhrase;
  }
}

export default class TypeUtil {

  static isPrimitive(value) {
    if (TypeUtil.isTraversable(value)
        || TypeUtil.isFunction(value)) {
          return false;
    }

    return true;
  }

  static isObject(value) {
    if (TypeUtil.isArray(value)) {
      return false;
    }

    if (TypeUtil.isFunction(value)) {
      return false;
    }

    return value === Object(value);
  }

  static isFunction(value) {
    return !!(value && value.constructor && value.call && value.apply);
  }

  static isArray(value) {
    return Array.isArray(value);
  }

  static isTraversable(value) {
    return TypeUtil.isArray(value) || TypeUtil.isObject(value);
  }

  static isNumber(value) {
    return typeof value === 'number';
  }

  static isLegalNumber(value) {
    if (isNaN(value)) {
      return false;
    }

    return typeof value === 'number';
  }

  static isString(value) {
    return typeof value === 'string';
  }

  static isBoolean(value) {
    return value === true || value === false;
  }
}

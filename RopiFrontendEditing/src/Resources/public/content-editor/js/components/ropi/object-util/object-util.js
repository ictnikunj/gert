import TypeUtil from '../type-util/type-util.js?v=1637255330';

export default class ObjectUtil {

  static setKeyPath(object, keyPath, value) {
    if (TypeUtil.isPrimitive(object) || !keyPath) return;

    let keyPathSegments = keyPath.split('.');
    let childObject = object;

    for (let i = 0; i < keyPathSegments.length; i++) {
      let keyPathSegment = keyPathSegments[i];
      let isLast = (i >= (keyPathSegments.length - 1));

      if (isLast) {
        childObject[keyPathSegment] = value;
        return;
      }

      if (childObject[keyPathSegment] == null) {
        childObject[keyPathSegment] = {};
      }

      childObject = childObject[keyPathSegment];
    }
  }

  static getKeyPath(object, keyPath) {
    if (TypeUtil.isPrimitive(object) || !keyPath) return;

    let keyPathSegments = keyPath.split('.');
    let childObject = object;

    for (let i = 0; i < keyPathSegments.length; i++) {
      let keyPathSegment = keyPathSegments[i];
      let isLast = (i >= (keyPathSegments.length - 1));

      if (isLast) {
        return childObject[keyPathSegment];
      }

      if (childObject[keyPathSegment] == null) {
        return;
      }

      childObject = childObject[keyPathSegment];
    }
  }

  static flatten(object, startDelimiter, endDelimiter) {
    if (startDelimiter == null && endDelimiter == null) {
      startDelimiter = '.';
      endDelimiter = '';
    }

    startDelimiter = startDelimiter == null ? '' : startDelimiter;
    endDelimiter = endDelimiter == null ? '' : endDelimiter;

    if (TypeUtil.isPrimitive(object)) {
      return object;
    }

    let flattendObject = {};

    for (let key of Object.keys(object)) {
      if (TypeUtil.isPrimitive(object[key])) {
        flattendObject[key] = object[key];
        continue;
      }

      let flattendChildObject = ObjectUtil.flatten(object[key]);
      for (let childKey of Object.keys(flattendChildObject)) {
        let deepKey = key + startDelimiter + childKey + endDelimiter;
        flattendObject[deepKey] = flattendChildObject[childKey];
      }
    }

    return flattendObject;
  }
}

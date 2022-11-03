import TypeUtil from '../type-util/type-util.js?v=1637255330';

export default class QueryString {

  static get NESTING_MODE_FLAT() { return 0; }

  static get NESTING_MODE_SQUARE_BRACKETS() { return 1; }

  static get NESTING_MODE_DOT() { return 2; }

  static _normalizeNestingMode(nestingMode) {
    nestingMode = parseInt(nestingMode, 10);
    if (isNaN(nestingMode) || nestingMode < 0 || nestingMode > 2) {
      nestingMode = QueryString._defaultNestingMode;
    }

    nestingMode = parseInt(nestingMode, 10);
    if (isNaN(nestingMode) || nestingMode < 0 || nestingMode > 2) {
      return QueryString.NESTING_MODE_DOT;
    }

    return nestingMode;
  }

  static set defaultNestingMode(defaultNestingMode) {
    QueryString._defaultNestingMode = defaultNestingMode;
  }

  static get defaultNestingMode() {
    return QueryString._normalizeNestingMode(
      QueryString._defaultNestingMode
    );
  }

  static stringify(value, nestingMode) {
    return QueryString._stringify(
      value,
      '',
      QueryString._normalizeNestingMode(nestingMode)
    );
  }

  static _stringify(value, rootKey, nestingMode) {
    let queryComponents = [];

    if (TypeUtil.isArray(value) || TypeUtil.isObject(value)) {
      for (let key of Object.keys(value)) {
        if (rootKey === '') {
          queryComponents.push(
            QueryString._stringify(
              value[key],
              key,
              nestingMode
            )
          );
        } else {
          queryComponents.push(
            QueryString._stringify(
              value[key],
              (
                (nestingMode === QueryString.NESTING_MODE_SQUARE_BRACKETS)
                ? rootKey + '[' + key + ']'
                : rootKey + '.' + key
              ),
              nestingMode
            )
          );
        }
      }
    } else {
      if (rootKey === '') {
        return encodeURIComponent(value);
      }

      queryComponents.push(
        encodeURIComponent(rootKey)
        + '='
        + encodeURIComponent(value)
      );
    }

    return queryComponents.join('&');
  }

  static parse(string, nestingMode) {
    nestingMode = QueryString._normalizeNestingMode(nestingMode);

    let queryComponents = string.replace(/.*\?/, '').split('&');
    let result = {};

    for (let queryComponent of queryComponents) {
      if (!queryComponent.trim()) {
        continue;
      }

      let [key, value] = queryComponent.split('=', 2);

      key = decodeURIComponent(key);
      value = decodeURIComponent(value == null ? '' : value);

      if (nestingMode === QueryString.NESTING_MODE_FLAT) {
        result[key] = value;
      } else {
        let chars = Array.from(key);
        let lastProperty = result;
        let currentPropertyName = '', lastPropertyName = '';

        for (let i = 0; i < chars.length; i++) {
          let char = chars[i];
          let isLast = (i === (chars.length - 1));

          if (nestingMode === QueryString.NESTING_MODE_DOT) {
            if (char === '.') {
              if (!lastProperty[currentPropertyName]) {
                lastProperty[currentPropertyName] = {};
              }

              lastProperty = lastProperty[currentPropertyName];
              lastPropertyName = currentPropertyName;
              currentPropertyName = '';
              continue;
            }
          } else {
            if (char === ']') {
              continue;
            }

            if (char === '[') {
              if (i === 0) {
                // Param starts with square bracket (e.g. []=test),
                // which is invalid
                continue;
              }

              if (chars[i + 1] === ']') {
                if (!lastProperty[currentPropertyName]) {
                  lastProperty[currentPropertyName] = [];
                }

                lastProperty = lastProperty[currentPropertyName];
                lastPropertyName = 0;
                currentPropertyName = 0;
                continue;
              }

              if (!lastProperty[currentPropertyName]) {
                lastProperty[currentPropertyName] = {};
              }

              lastProperty = lastProperty[currentPropertyName];
              lastPropertyName = currentPropertyName;
              currentPropertyName = '';
              continue;
            }
          }

          currentPropertyName += char;
        }

        if (currentPropertyName !== '') {
          if (lastProperty[currentPropertyName]
              && !TypeUtil.isArray(lastProperty[currentPropertyName])) {
                lastProperty[currentPropertyName] = [lastProperty[currentPropertyName]];
          }

          if (TypeUtil.isArray(lastProperty[currentPropertyName])) {
            lastProperty[currentPropertyName].push(value);
          } else {
            lastProperty[currentPropertyName] = value;
          }
        }
      }
    }

    return result;
  }
}

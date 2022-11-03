import TypeUtil from '../type-util/type-util.js?v=1637255330';
import ObjectUtil from '../object-util/object-util.js?v=1637255330';

export default class StringTemplate {

  constructor(string, vars, keepUndefinedPlaceholders, valueEncoder) {
    this.string = string;
    this.vars = vars;
    this.keepUndefinedPlaceholders = keepUndefinedPlaceholders;
    this.valueEncoder = valueEncoder;
  }

  set string(string) {
    this._string = string == null ? '' : string.toString();
  }

  get string() {
    return this._string;
  }

  set vars(vars) {
    this._vars = vars == null ? {} : vars;
  }

  get vars() {
    return this._vars;
  }

  set valueEncoder(valueEncoder) {
    this._valueEncoder = valueEncoder;
  }

  get valueEncoder() {
    return this._valueEncoder;
  }

  set keepUndefinedPlaceholders(keepUndefinedPlaceholders) {
    if (keepUndefinedPlaceholders) {
      this._keepUndefinedPlaceholders = true;
    } else {
      this._keepUndefinedPlaceholders = false;
    }
  }

  get keepUndefinedPlaceholders() {
    return this._keepUndefinedPlaceholders;
  }

  process() {
    let string = this.string;

    let placeholders = StringTemplate.parsePlaceholders(string);

    for (let placeholder of placeholders) {
      let variableName = StringTemplate.parsePlaceholderName(placeholder);
      let value = ObjectUtil.getKeyPath(this._vars, variableName);

      if (value == null) {
        if (this.keepUndefinedPlaceholders) {
          // Keep placeholders -> no further processing
          continue;
        }

        value = '';
      }

      if (TypeUtil.isFunction(this.valueEncoder)) {
        value = this.valueEncoder(value, variableName);
      }

      while (string.indexOf(placeholder) !== -1) {
        string = string.replace(placeholder, String(value));
      }
    }

    return string;
  }

  static process(string, vars, keepUndefinedPlaceholders, valueEncoder) {
    return (new StringTemplate(
      string,
      vars,
      keepUndefinedPlaceholders,
      valueEncoder
    )).process();
  }

  static parsePlaceholderName(placeholder) {
    return String(placeholder).replace(/[\{\}\#]/g, '').trim();
  }

  static parsePlaceholders(string) {
    let placeholders = String(string).match(/[\{\#][\{\#]\s*[a-zA-Z0-9\.]+\s*[\}\#][\}\#]/gi);

    if (!(placeholders instanceof Array)) {
      return [];
    }

    return placeholders;
  }
}

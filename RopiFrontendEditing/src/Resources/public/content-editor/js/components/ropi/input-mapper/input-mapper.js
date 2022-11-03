import TypeUtil from '../type-util/type-util.js?v=1637255330';
import ObjectUtil from '../object-util/object-util.js?v=1637255330';

export default class InputMapper {

  static get NESTING_MODE_SQUARE_BRACKETS() { return 1; }

  static get NESTING_MODE_DOT() { return 2; }

  static set nestingMode(nestingMode) {
    if (nestingMode === InputMapper.NESTING_MODE_SQUARE_BRACKETS
        || nestingMode === InputMapper.NESTING_MODE_DOT) {
          InputMapper._nestingMode = nestingMode;
    }
  }

  static get nestingMode() {
    return InputMapper._nestingMode || InputMapper.NESTING_MODE_DOT;
  }

  constructor(element) {
    this._element = element;
  }

  set nestingMode(nestingMode) {
    if (nestingMode === InputMapper.NESTING_MODE_SQUARE_BRACKETS
        || nestingMode === InputMapper.NESTING_MODE_DOT) {
          this._nestingMode = nestingMode;
    }
  }

  get nestingMode() {
    return this._nestingMode || InputMapper.nestingMode;
  }

  _flattenValues(values) {
    if (this.nestingMode === InputMapper.NESTING_MODE_SQUARE_BRACKETS) {
      return ObjectUtil.flatten(values, '[', ']');
    }

    return ObjectUtil.flatten(values);
  }

  set values(values) {
    let flattendValues = this._flattenValues(values);
    for (let keyPath of Object.keys(flattendValues)) {
      let value = flattendValues[keyPath];
      let inputElements = this._element.querySelectorAll(`[name="${keyPath}"]`);

      // Find nearest parent
      if (inputElements.length === 0) {
        let segments = keyPath.split('.');
        while(segments.pop() && segments.length > 0) {
          keyPath = segments.join('.');
          inputElements = this._element.querySelectorAll(`[name="${keyPath}"]`);
          if (inputElements.length > 0) {
            value = ObjectUtil.getKeyPath(values, keyPath);
            continue;
          }
        }
      }

      for (let inputElement of inputElements) {
        if (inputElement.type === undefined) {
          continue;
        }

        if (inputElement.type === 'radio' || inputElement.type === 'checkbox') {
          if (inputElement.value === value) {
            inputElement.checked = true;
          } else {
            inputElement.checked = false;
          }
        } else if (inputElement.value !== undefined) {
          inputElement.value = value;
        }
      }
    }
  }

  get values() {
    let values = {};
    let inputElements = this._element.querySelectorAll('[name]');

    for (let inputElement of inputElements) {
      if (inputElement.type === undefined) {
        continue;
      }

      let name = inputElement.getAttribute('name');
      if (!name) {
        continue;
      }

      let keyPath = name;
      if (this.nestingMode === InputMapper.NESTING_MODE_SQUARE_BRACKETS) {
        keyPath = keyPath.replace(/\[/g, '.').replace(/\]/g, '');
      }

      let value = inputElement.value;

      if ((inputElement.type === 'checkbox' || inputElement.type === 'radio')
          && !inputElement.checked) {
            value = '';
      }

      if (inputElement.type === 'radio') {
        let currentValue = ObjectUtil.getKeyPath(values, keyPath);
        if (currentValue && !inputElement.checked) {
          continue;
        }
      }

      ObjectUtil.setKeyPath(values, keyPath, value);
    }

    return values;
  }
}

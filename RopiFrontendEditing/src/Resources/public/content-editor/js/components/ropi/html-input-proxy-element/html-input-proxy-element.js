import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';

export default class extends RopiHTMLElement {

  static get observedAttributes() {
    return [
      'autofocus',
      'accesskey',
      'disabled',
      'value',
      'required',
      'pattern',
      'maxlength',
      'minlength',
      'min',
      'max',
      'readonly',
      'size',
      'name'
    ];
  }

  constructor() {
    super();

    this._htmlInputFocused = false;

    this._htmlInputFocusHandler = () => {
      this._htmlInputFocused = true;

      this.setAttribute('focused', '');
    };

    this._htmlInputBlurHandler = () => {
      this._htmlInputFocused = false;

      this.removeAttribute('focused');
    };

    this._htmlInputChangeHandler = () => {
      this.dispatchEvent(new CustomEvent('change', {
        bubbles: true
      }));
    };
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (this.hasAttribute(name)) {
      this._input.setAttribute(name, value);
    } else {
      this._input.removeAttribute(name);
    }
  }

  connectedCallback() {
    this._input.addEventListener('focus', this._htmlInputFocusHandler);
    this._input.addEventListener('blur', this._htmlInputBlurHandler);
    this._input.addEventListener('change', this._htmlInputChangeHandler);
  }

  disconnectedCallback() {
    this._input.removeEventListener('focus', this._htmlInputFocusHandler);
    this._input.removeEventListener('blur', this._htmlInputBlurHandler);
    this._input.removeEventListener('change', this._htmlInputChangeHandler);
  }

  focus() {
    return this._input.focus(...arguments);
  }

  blur() {
    return this._input.blur(...arguments);
  }

  select() {
    return this._input.select(...arguments);
  }

  setSelectionRange() {
    return this._input.setSelectionRange(...arguments);
  }

  setRangeText() {
    return this._input.setRangeText(...arguments);
  }

  checkValidity() {
    return this._input.checkValidity(...arguments);
  }

  reportValidity() {
    return this._input.reportValidity(...arguments);
  }

  setCustomValidity() {
    return this._input.setCustomValidity(...arguments);
  }

  get validity() {
    return this._input.validity;
  }

  get validationMessage() {
    return this._input.validationMessage;
  }

  get willValidate() {
    return this._input.willValidate;
  }

  set value(value) {
    this._input.value = value;
  }

  get value() {
    return this._input.value;
  }

  get type() {
    return this._input.type;
  }

  get focused() {
    return this._htmlInputFocused;
  }

  get required() {
    return this._input.required;
  }

  set required(required) {
    this._input.required = required;
  }

  get name() {
    return this._input.name;
  }

  set name(name) {
    this._input.name = name;
  }

  get max() {
    return this._input.max;
  }

  set max(max) {
    this._input.max = max;
  }

  get maxLength() {
    return this._input.maxLength;
  }

  set maxLength(maxLength) {
    this._input.maxLength = maxLength;
  }

  get min() {
    return this._input.min;
  }

  set min(min) {
    this._input.min = min;
  }

  get minLength() {
    return this._input.minLength;
  }

  set minLength(minLength) {
    this._input.minLength = minLength;
  }

  get pattern() {
    return this._input.pattern;
  }

  set pattern(pattern) {
    this._input.pattern = pattern;
  }

  get placeholder() {
    return this._input.placeholder;
  }

  set placeholder(placeholder) {
    this._input.placeholder = placeholder;
  }

  get autofocus() {
    return this._input.autofocus;
  }

  set autofocus(autofocus) {
    this._input.autofocus = autofocus;
  }

  get readOnly() {
    return this._input.readOnly;
  }

  set readOnly(readOnly) {
    this._input.readOnly = readOnly;
  }

  get selectionStart() {
    return this._input.selectionStart;
  }

  set selectionStart(selectionStart) {
    this._input.selectionStart = selectionStart;
  }

  get selectionEnd() {
    return this._input.selectionEnd;
  }

  set selectionEnd(selectionEnd) {
    this._input.selectionEnd = selectionEnd;
  }

  get selectionDirection() {
    return this._input.selectionDirection;
  }

  set selectionDirection(selectionDirection) {
    this._input.selectionDirection = selectionDirection;
  }

  get size() {
    return this._input.size;
  }

  set size(size) {
    this._input.size = size;
  }

  get defaultValue() {
    return this._input.defaultValue;
  }

  set defaultValue(defaultValue) {
    this._input.defaultValue = defaultValue;

    if (this._input.hasAttribute('value')) {
      this.setAttribute('value', this._input.defaultValue);
    } else {
      this.removeAttribute('value');
    }
  }

  get multiple() {
    return this._input.multiple;
  }

  set multiple(multiple) {
    this._input.multiple = multiple;
  }

  get accessKey() {
    return this._input.accessKey;
  }

  set accessKey(accessKey) {
    this._input.accessKey = accessKey;
  }

  get tabIndex() {
    return this._input.tabIndex;
  }

  set tabIndex(tabIndex) {
    this._input.tabIndex = tabIndex;
  }

  get dirName() {
    return this._input.dirName;
  }

  set dirName(dirName) {
    this._input.dirName = dirName;
  }

  get input() {
    return this._input;
  }
}

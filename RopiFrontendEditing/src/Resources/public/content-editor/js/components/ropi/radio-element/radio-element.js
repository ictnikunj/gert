import RopiHTMLInputProxyElement from '../html-input-proxy-element/html-input-proxy-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';
import '../touchable-element/touchable-element.js?v=1637255330';

export default class RopiRadioElement extends RopiHTMLInputProxyElement {

  static get observedAttributes() {
    return RopiHTMLInputProxyElement.observedAttributes.concat(['checked']);
  }

  constructor() {
    super();

    this._input = this.shadowRoot.getElementById('radio');
    this._touchable = this.shadowRoot.querySelector('ropi-touchable');

    this._changeHandler = (event) => {
      this.checked = event.currentTarget.checked;

      if (this.checked) {
        this.dispatchEvent(new CustomEvent('check', {
          bubbles: true
        }));
      }
    }

    this._focusHandler = () => {
      this._htmlInputFocusHandler();
    };

    this._blurHandler = () => {
      this._htmlInputBlurHandler();
    };

    this._keypressHandler = (event) => {
      if (this.hasAttribute('disabled') || this.hasAttribute('readonly')) {
        return;
      }

      if (event.keyCode === 13) {
        let checkedBefore = this.checked;

        this.checked = true;

        if (!checkedBefore) {
          this.dispatchEvent(new CustomEvent('change', {
            bubbles: true
          }));

          this.dispatchEvent(new CustomEvent('check', {
            bubbles: true
          }));
        }
      }
    };
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (this.hasAttribute('disabled')) {
      this._touchable.setAttribute('disabled', '');
      this.setAttribute('aria-disabled', 'true');
    } else {
      this._touchable.removeAttribute('disabled');
      this.removeAttribute('aria-disabled');
    }

    if (name === 'required') {
      if (this.hasAttribute('required')) {
        this.setAttribute('aria-required', 'true');
      } else {
        this.removeAttribute('aria-required');
      }
    } else if (name === 'readonly') {
      if (this.hasAttribute('readonly')) {
        this.setAttribute('aria-readonly', 'true');
      } else {
        this.removeAttribute('aria-readonly');
      }
    }

    super.attributeChangedCallback(name, valueBefore, value);

    if (name === 'checked') {
      if (this.hasAttribute('checked')) {
        this._input.setAttribute('checked', '');
        this.checked = true;
      } else {
        this._input.removeAttribute('checked');
      }
    }
  }

  connectedCallback() {
    super.connectedCallback();

    this.attributeChangedCallback();
    this.setAttribute('role', this.getAttribute('role') || 'radio');

    this._input.addEventListener('change', this._changeHandler);

    this._touchable.addEventListener('focus', this._focusHandler);
    this._touchable.addEventListener('blur', this._blurHandler);
    this.addEventListener('keypress', this._keypressHandler);
  }

  disconnectedCallback() {
    this._input.removeEventListener('change', this._changeHandler);
    this._touchable.removeEventListener('focus', this._focusHandler);
    this._touchable.removeEventListener('blur', this._blurHandler);
    this.removeEventListener('keypress', this._keypressHandler);
  }

  set checked(checked) {
    this._input.checked = checked;
    this.setAttribute('aria-checked', this._input.checked ? 'true' : 'false');
  }

  get checked() {
    return this._input.checked;
  }

  set defaultChecked(defaultChecked) {
    this._input.defaultChecked = defaultChecked;
  }

  get defaultChecked() {
    return this._input.defaultChecked;
  }

  set indeterminate(indeterminate) {
    this._input.indeterminate = indeterminate;
  }

  get indeterminate() {
    return this._input.indeterminate;
  }
}

RopiRadioElement._template = html`
<style>
  :host {
    outline: none;
    display: block;
    color: var(--ropi-color-font-75, grey);
  }

  ropi-touchable[disabled] {
    opacity: 0.5;
  }

  #container {
    display: block;
    color: var(--ropi-color-font-100, grey);
    outline: none !important;
    padding: var(--ropi-grid-outer-gutter-height, 0.75rem)
             var(--ropi-grid-outer-gutter-width, 1rem);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  input {
    display: none;
  }

  #circle,
  #text {
    vertical-align: middle;
    line-height: 1.25rem;
  }

  #circle,
  #circle::after {
    border: 0.125rem solid var(--ropi-radio-color-unchecked, var(--ropi-color-base-contrast-medium, grey));
    display: inline-block;
    width: 0.75rem;
    height: 0.75rem;
    margin: 0.25rem;
    margin-right: calc(0.25rem + var(--ropi-grid-outer-gutter-height, 0.75rem));
    border-radius: 100%;
    position: relative;
  }

  #circle::after {
    margin: 0;
    content: " ";
    border: none;
    width: 100%;
    height: 100%;
    position: absolute;
    transform: scale(0.1);
    opacity: 0;
    transition: transform var(--ropi-animation-duration, 301ms) ease,
                opacity var(--ropi-animation-duration, 301ms) ease;
    background-color: var(--ropi-radio-color, var(--ropi-color-interactive, blue));
  }

  input:checked + #circle {
    border: 0.125rem solid var(--ropi-radio-color, var(--ropi-color-interactive, blue));
  }

  input:checked + #circle::after {
    transform: scale(1.15);
    opacity: 1;
  }
</style>
<ropi-touchable role="presentation">
  <label id="container" for="radio">
    <input id="radio" type="radio" role="presentation" />
    <span id="circle"></span>
    <span id="text">
      <slot></slot>
    </span>
  </label>
</ropi-touchable>`;

customElements.define('ropi-radio', RopiRadioElement);

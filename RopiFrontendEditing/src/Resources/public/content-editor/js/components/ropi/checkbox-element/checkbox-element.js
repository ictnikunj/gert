import RopiHTMLInputProxyElement from '../html-input-proxy-element/html-input-proxy-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';
import '../touchable-element/touchable-element.js?v=1637255330';

export default class RopiCheckboxElement extends RopiHTMLInputProxyElement {

  static get observedAttributes() {
    return RopiHTMLInputProxyElement.observedAttributes.concat(['checked']);
  }

  constructor() {
    super();

    this._input = this.shadowRoot.getElementById('checkbox');
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
        this.checked = !this.checked;

        this.dispatchEvent(new CustomEvent('change', {
          bubbles: true
        }));

        if (this.checked) {
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
    this.setAttribute('role', this.getAttribute('role') || 'checkbox');

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

RopiCheckboxElement._template = html`
<style>
  :host {
    outline: none;
    display: block;
    color: var(--ropi-color-font-75, grey);
  }

  :host([invalid]) #box {
    border-color: var(--ropi-color-error, red) !important;
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

  #box,
  #text {
    vertical-align: middle;
    line-height: 1.25rem;
  }

  #box,
  #box::before {
    border: 0.125rem solid var(--ropi-checkbox-color-unchecked, var(--ropi-color-base-contrast-medium, grey));
    display: inline-block;
    width: 0.75rem;
    height: 0.75rem;
    margin: 0.25rem;
    margin-right: calc(0.25rem + var(--ropi-grid-outer-gutter-height, 0.75rem));
    position: relative;
  }

  #box::before {
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
    background-color: var(--ropi-checkbox-color, var(--ropi-color-interactive, blue));
  }

  input:checked + #box {
    border: 0.125rem solid var(--ropi-checkbox-color, var(--ropi-color-interactive, blue));
  }

  input:checked + #box::before {
    transform: scale(1.15);
    opacity: 1;
  }
</style>
<ropi-touchable role="presentation">
  <label id="container" for="checkbox">
    <input id="checkbox" type="checkbox" role="presentation" />
    <span id="box"></span>
    <span id="text">
      <slot></slot>
    </span>
  </label>
</ropi-touchable>`;

customElements.define('ropi-checkbox', RopiCheckboxElement);

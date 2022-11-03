import RopiHTMLInputProxyElement from '../html-input-proxy-element/html-input-proxy-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

import TypeUtil from '../type-util/type-util.js?v=1637255330';
import HttpRequest from '../http-message/http-request.js?v=1637255330';
import HttpClient from '../http-client/http-client.js?v=1637255330';
import ObjectUtil from "../object-util/object-util.js?v=1637255330";
import StringTemplate from "../string-template/string-template.js?v=1637255330";

import '../material-icon-element/material-icon-element.js?v=1637255330';
import '../for-element/for-element.js?v=1637255330';
import '../touchable-element/touchable-element.js?v=1637255330';

export default class RopiTextfieldElement extends RopiHTMLInputProxyElement {

  static get observedAttributes() {
    return RopiHTMLInputProxyElement.observedAttributes.concat([
      'type'
    ]);
  }

  get supportedTypes() {
     return ['text', 'email', 'number', 'password', 'tel', 'url', 'select'];
  }

  _normalizeType(type) {
    type = String(type).toLowerCase();

    if (type !== 'select' && this.supportedTypes.includes(type)) {
      return type;
    }

    return 'text';
  }

  constructor() {
    super();

    this._selected = null;
    this._httpClient = new HttpClient();
    this._container = this.shadowRoot.getElementById('container');
    this._input = this.shadowRoot.getElementById('input');
    this._suggest = this.shadowRoot.getElementById('suggest');
    this._suggestFor = this.shadowRoot.querySelector('ropi-for[as="item"]');
    this._clearButton = this.shadowRoot.getElementById('clear-button');

    this._changeHandler = () => {
      this._updateValueState();
    };

    this._focusHandler = () => {
      if (this.getAttribute('type') === 'select') {
        if (this._suggestTimeout) {
          clearTimeout(this._suggestTimeout);
        }

        this._input.value = '';
        this._updateSuggest();
      }
    };

    this._keydownHandler = (event) => {
      if (this.getAttribute('type') === 'select') {
        let current = this._suggest.querySelector('ropi-touchable[focused]');
        if (!current) {
          return;
        }

        if (event.keyCode === 40) {
          // DOWN
          let next = current.nextElementSibling;
          if (next && next.nodeName === current.nodeName) {
            current.removeAttribute('focused');
            next.setAttribute('focused', '');
          }
        } else if (event.keyCode === 38) {
          // UP
          let previous = current.previousElementSibling;
          if (previous && previous.nodeName === current.nodeName) {
            current.removeAttribute('focused');
            previous.setAttribute('focused', '');
          }
        } else if (event.keyCode == 13) {
          // ENTER
          event.preventDefault();
          current.click();
          this._input.blur();
          this._hideSuggest();
        }
      }
    };

    this._inputHandler = () => {
      if (this.getAttribute('type') === 'select') {
        this._updateSuggest();
      }
    };

    this._blurHandler = (event) => {
      if (this.getAttribute('type') === 'select') {
        if (document.activeElement === this) {
          return;
        }

        this._suggestTimeout = setTimeout(() => {
          this._hideSuggest(true);
          this._updateValueState();
        }, 100);
      }
    };

    this._clearButtonClickHandler = () => {
      this.value = '';

      this.dispatchEvent(new CustomEvent('change', {
        bubbles: true
      }));
    };
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (name === 'type') {
      this._input.setAttribute('type', this._normalizeType(value));

      if (value === 'select') {
        this._input.addEventListener('focus', this._focusHandler);
        this._input.addEventListener('keydown', this._keydownHandler);
        this._input.addEventListener('input', this._inputHandler);
        this._input.addEventListener('blur', this._blurHandler);
      } else {
        this._input.removeEventListener('focus', this._focusHandler);
        this._input.removeEventListener('keydown', this._keydownHandler);
        this._input.removeEventListener('input', this._inputHandler)
        this._input.removeEventListener('blur', this._blurHandler);
      }

      return;
    }

    super.attributeChangedCallback(name, valueBefore, value);
  }

  connectedCallback() {
    super.connectedCallback();

    this._update();

    this._observer = new MutationObserver(() => {
      this._update();
    });

    this._observer.observe(this, {
      childList: true,
      characterData: true,
      subtree: true
    });

    this._input.addEventListener('change', this._changeHandler);
    this._clearButton.addEventListener('click', this._clearButtonClickHandler);
  }

  disconnectedCallback() {
    super.disconnectedCallback();

    if (this._observer) {
      this._observer.disconnect();
      delete this._observer;
    }

    this._input.removeEventListener('change', this._changeHandler);
    document.removeEventListener('click', this._clickOutsideHandler);
  }

  _update() {
    this._updateValueState();
  }

  _buildItemsRequest() {
    let request = (new HttpRequest(this.getAttribute('items')))
                      .setMethod(HttpRequest.METHOD_GET);

    let parameters = {q: this._input.value};
    parameters['_ropi' + Date.now()] = '';

    request.setQueryParameters(parameters);

    return request;
  }

  _updateSuggest() {
    this._abortPendingRequest();

    this._pendingRequest = this._buildItemsRequest();

    this.dispatchEvent(new CustomEvent('updatesuggest', {
      detail: {
        request: this._pendingRequest
      }
    }));

    this._httpClient.send(this._pendingRequest)
    .then(JSON.parse)
    .then((responseObject) => {
      let dataProperty = this.getAttribute('dataproperty') || 'data';
      let valueProperty = this.getAttribute('valueproperty') || 'value';
      let labelformat = this.getAttribute('labelformat') || '{label}';

      this._hideSuggest();

      let rawData = ObjectUtil.getKeyPath(responseObject, dataProperty);

      if (!rawData || !TypeUtil.isArray(rawData) || rawData.length === 0) {
        rawData = [];
      }

      let data = [];

      rawData.forEach((item) => {
        data.push({
          label: StringTemplate.process(labelformat, item),
          value: ObjectUtil.getKeyPath(item, valueProperty)
        });
      });

      this._suggestFor.oniterate = (event) => {
        let touchable = event.detail.elements[0];

        if (event.detail.cycle === 1) {
          touchable.setAttribute('focused', '');
        }

        let clickHandler = () => {
          let item = touchable.querySelector('.suggest-item');

          this._selected = {
            value: item.getAttribute('data-value'),
            label: item.innerHTML
          };

          this._updateValueState();

          this.dispatchEvent(new CustomEvent('change', {
            bubbles: true
          }));
        };

        touchable.addEventListener('click', clickHandler, true);
        touchable.addEventListener('mousedown', clickHandler, true);
      };

      this._suggestFor.each = data;

      this._container.classList.add('not-empty');

      this._suggest.style.display = 'block';
      this._container.style.zIndex = '1';
    });
  }

  _hideSuggest(validate) {
    this._abortPendingRequest();
    this._suggest.style.display = 'none';
    this._container.style.zIndex = '';
    this._suggestFor.each = [];
  }

  _updateValueState() {
    if (this.getAttribute('type') === 'select') {
      if (this._selected) {
        this._input.value = this._selected.label;
      } else {
        this._input.value = '';
      }
    }

    if (this._input.value) {
      this._container.classList.add('not-empty');
    } else {
      this._container.classList.remove('not-empty');
    }
  }

  _abortPendingRequest() {
    if (this._pendingRequest) {
      this._httpClient.abort(this._pendingRequest);
      this._pendingRequest = null;
    }
  }

  set value(value) {
    this._selected = null;

    if (this.getAttribute('type') === 'select') {
      if (TypeUtil.isObject(value)) {
        if (value.label !== undefined || value.value !== undefined) {
          this._selected = {
            label: value.label,
            value: value.value
          };
        }
      } else {
        if (String(value).trim() === '') {
          this._selected = null;
          this._input.value = '';
          this._updateValueState();
          return;
        }

        try {
          let parsed = JSON.parse(value);
          if (parsed.label !== undefined && parsed.value !== undefined) {
            this._selected = {
              label: parsed.label,
              value: parsed.value,
            };
          }
        } catch (e) {
          // Fail silently
        }
      }

      if (!this._selected) {
        this._selected = {
          label: value,
          value: null
        };
      }
    } else {
      super.value = value;
    }

    this._updateValueState();
  }

  get value() {
    if (this.getAttribute('type') === 'select') {
      return this._selected;
    }

    return super.value.trim();
  }
}

RopiTextfieldElement._template = html`
<style>
  :host {
     background-color: var(--ropi-color-base);
     display: block;
     /*
      default should be 2.5rem but this is a workaround for chrome,
      because if type=number and you are trying to input alphabetic charset
      the cursor position wil be rendered incorrectly
     */
     height: var(--ropi-textfield-height, 2.499rem);
     line-height: var(--ropi-textfield-height, 2.499rem);
     padding: 0.45rem 0 1rem 0;

     --ropi-textfield-label-top: calc(var(--ropi-textfield-height, 2.499rem) * 0.5 - 0.5rem);
     --ropi-textfield-suggest-top: calc(var(--ropi-textfield-height, 2.499rem) + 0.0625rem);
  }

  :host([invalid]) > #container > label {
     color: var(--ropi-color-error, red) !important;
  }

  :host([invalid]) > #container {
     outline-color: var(--ropi-color-error, red) !important;
  }

  #container {
     position: relative;
     color: var(--ropi-color-font-100, grey);
     outline: 0.0625rem solid var(--ropi-color-base-contrast-medium, grey);
     background-color: inherit;
     z-index: 0;
     height: inherit;
  }

  :host([disabled]) #container {
     opacity: 0.5;
  }

  :host([focused]) #container {
     z-index: 1;
     outline: 0.125rem solid var(--ropi-textfield-color, var(--ropi-color-interactive, blue));
  }

  label {
     position: absolute;
     color: var(--ropi-color-font-50, grey);
     transform: translate(0) scale(1);
     transition: transform calc(var(--ropi-animation-duration, 301ms) * 0.5) ease;
     background-color: inherit;
     height: 1rem;
     line-height: 1rem;
     top: var(--ropi-textfield-label-top);
     left: var(--ropi-textfield-label-left, 0.75rem);
     transform-origin: left top;
     pointer-events: none;
  }

  :host([focused]) #container > label,
  #container.not-empty > label {
     transform: translate(0, calc(var(--ropi-textfield-label-top) - var(--ropi-textfield-height, 2.5rem) + 0.5rem)) scale(0.75);
  }

  :host([focused]) #container > label {
     color: var(--ropi-textfield-color, var(--ropi-color-interactive, blue));
  }

  #input {
     background: none;
     border: none;
     outline: none !important;
     color: inherit;
     font-size: inherit;
     font-family: inherit;
     display: block;
     width: 100%;
     height: inherit;
     line-height: inherit;
     margin: 0;
     text-overflow: ellipsis;
     padding: var(--ropi-textfield-padding, 0 0.75rem);
     box-sizing: border-box;
  }

  #input[type="number"] {
    -moz-appearance: textfield;
  }

  input::-webkit-outer-spin-button,
  input::-webkit-inner-spin-button {
    -webkit-appearance: none;
  }

  #suggest {
    display: none;
    position: absolute;
    top: var(--ropi-textfield-suggest-top);
    left: 0;
    width: 100%;
    border: solid 0.0625rem var(--ropi-color-contrast-medium, grey);
    background-color: var(--ropi-color-base, black);
  }

  .suggest-item {
    padding: var(--ropi-grid-outer-gutter-height, 0.75rem) var(--ropi-grid-outer-gutter-width, 1rem);
    line-height: 1rem;
  }

  #clear-button {
    display: none;
  }

  :host([type="select"]) #container.not-empty {
    padding-right: 2rem;
  }

  :host([type="select"]) #container.not-empty #clear-button {
    display: block;
    width: 2rem;
    height: var(--ropi-textfield-height, 2.499rem);
    line-height: var(--ropi-textfield-height, 2.499rem);
    text-align: center;
    position: absolute;
    right: 0;
    top: 0;
  }
</style>
<div id="container">
  <label for="input"><slot name="placeholder"></slot></label>
  <input id="input"
     type="text"
     spellcheck="false"
     autocomplete="off"
     autocorrect="off"
     autocapitalize="off"
  />
  <div id="clear-button">
    <ropi-material-icon>clear</ropi-material-icon>
  </div>
  <div id="suggest">
    <ropi-for as="item">
      <ropi-touchable class="suggest-touchable">
        <div class="suggest-item" data-value="{{item.value}}" data-item="label"></div>
      </ropi-touchable>
    </ropi-for>
  </div>
</div>`;

customElements.define('ropi-textfield', RopiTextfieldElement);

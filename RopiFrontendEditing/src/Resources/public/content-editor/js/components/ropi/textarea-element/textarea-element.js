import RopiHTMLInputProxyElement from '../html-input-proxy-element/html-input-proxy-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiTextareaElement extends RopiHTMLInputProxyElement {

 constructor() {
   super();

   this._container = this.shadowRoot.getElementById('container');
   this._input = this.shadowRoot.getElementById('input');
   this._shadowInput = this.shadowRoot.getElementById('shadow-input');

   this._changeHandler = () => {
     this._updateValueState();
   };

   this._keydownHandler = (event) => {
     let newChar = String.fromCharCode(event.keyCode);
     let currentValue = this._input.value;

     this._updateSize(currentValue + newChar);
   };

   this._keyupHandler = (event) => {
     this._updateSize();
   };
 }

 attributeChangedCallback(name, valueBefore, value) {
   if (name === 'value') {
     this._input.textContent = value;
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
   this._input.addEventListener('keydown', this._keydownHandler, {passive: true});
   this._input.addEventListener('keyup', this._keyupHandler, {passive: true});
 }

 disconnectedCallback() {
   if (this._observer) {
     this._observer.disconnect();
     delete this._observer;
   }

   this._input.removeEventListener('change', this._changeHandler);
   this._input.removeEventListener('keydown', this._keydownHandler, {passive: true});
   this._input.removeEventListener('keyup', this._keyupHandler, {passive: true});
 }

 _update() {
   this._updateValueState();
 }

 _updateValueState() {
   if (this._input.value) {
     this._container.classList.add('not-empty');
   } else {
     this._container.classList.remove('not-empty');
   }

   this._updateSize();
 }

 _updateSize(forValue) {
   forValue = (forValue == null) ? this._input.value : forValue;
   this._shadowInput.value = forValue;
   this._shadowInput.style.display = 'block';
   this._input.style.height = this._shadowInput.scrollHeight + 'px';
   this._shadowInput.style.display = '';
 }

 set value(value) {
   super.value = value;

   this._updateValueState();
 }

 get value() {
   return super.value.trim();
 }

 set defaultValue(value) {
   this._input.defaultValue = value;
 }

 get textLength() {
   return this._input.textLength;
 }

 get cols() {
   return this._input.cols;
 }

 set cols(cols) {
   this._input.cols = cols;
 }

 get rows() {
   return this._input.rows;
 }

 set rows(rows) {
   this._input.rows = rows;
 }
}

RopiTextareaElement._template = html`
<style>
  :host {
    background-color: var(--ropi-color-base);
    display: block;
    padding: 0.45rem 0 1rem 0;
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
    padding: 0 var(--ropi-grid-outer-gutter-width, 1rem);
    outline: 0.0625rem solid var(--ropi-color-base-contrast-medium, grey);
    background-color: inherit;
    z-index: 0;
  }

  :host([disabled]) #container {
    opacity: 0.5;
  }

  :host([focused]) #container {
    z-index: 1;
    outline: 0.125rem solid var(--ropi-textarea-color, var(--ropi-color-interactive, blue));
  }

  label {
    position: absolute;
    color: var(--ropi-color-font-50, grey);
    transform: translate(0) scale(1);
    transition: transform calc(var(--ropi-animation-duration, 301ms) * 0.5) ease;
    background-color: inherit;
    height: 1rem;
    line-height: 1rem;
    top: 0.75rem;
    left: var(--ropi-grid-outer-gutter-width, 1rem);
    transform-origin: left top;
    pointer-events: none;
  }

  :host([focused]) #container > label,
  #container.not-empty > label {
    transform: translate(0, -1.25rem) scale(0.75);
  }

  :host([focused]) #container > label {
    color: var(--ropi-textarea-color, var(--ropi-color-interactive, blue));
  }

  #input,
  #shadow-input {
    background: none;
    border: none;
    outline: none !important;
    color: inherit;
    font-size: inherit;
    font-family: inherit;
    display: block;
    width: 100%;
    height: auto;
    min-height: 2.5rem;
    line-height: inherit;
    padding: 0.5rem 0 0 0;
    margin: 0;
    overflow: hidden;
  }

  #shadow-input {
    visibility: hidden;
    pointer-events: none;
    display: none;
    overflow: auto;
    position: absolute;
    left: 0;
    top: 0;
  }
</style>
<div id="container">
  <label for="input"><slot name="placeholder"></slot></label>
  <textarea id="input"></textarea>
  <textarea id="shadow-input" tabindex="-1" role="presentation"></textarea>
</div>`;

customElements.define('ropi-textarea', RopiTextareaElement);

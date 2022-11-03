import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';
import RopiRadioElement from './radio-element.js?v=1637255330';

export default class RopiRadioGroupElement extends RopiHTMLElement {

  constructor() {
    super();

    this._changeHandler = (event) => {
      if (event.target instanceof RopiRadioElement) {
        this.uncheckAll();
        event.target.checked = true;
      }
    };

    this.addEventListener('change', this._changeHandler, true);
  }

  connectedCallback() {
    this.setAttribute('role', this.getAttribute('role') || 'radiogroup');
  }

  uncheckAll() {
    let radioElements = this.querySelectorAll('ropi-radio:not([disabled])');
    for (let radioElement of radioElements) {
      radioElement.checked = false;
    }
  }

  checkFirst() {
    this.uncheckAll();

    let radioElement = this.querySelector('ropi-radio:not([disabled])');
    if (radioElement) {
      radioElement.checked = true;
    }
  }

  disableAll() {
    let radioElements = this.querySelectorAll('ropi-radio');
    for (let radioElement of radioElements) {
      radioElement.setAttribute('disabled', '');
    }
  }

  enableAll() {
    let radioElements = this.querySelectorAll('ropi-radio');
    for (let radioElement of radioElements) {
      radioElement.removeAttribute('disabled');
    }
  }

  get checkedValue() {
    let radioElements = this.querySelectorAll('ropi-radio:not([disabled])');

    for (let radioElement of radioElements) {
      if (radioElement.checked) {
        return radioElement.value;
      }
    }

    return '';
  }

  set checkedValue(value) {
    value = String(value == null ? '' : value);

    let radioElements = this.querySelectorAll('ropi-radio:not([disabled])');
    for (let radioElement of radioElements) {
      if (radioElement.value === value) {
        radioElement.checked = true;
      } else {
        radioElement.checked = false;
      }
    }
  }
}

RopiRadioGroupElement._template = html`
<style>
  :host {
    display: block;
  }
</style>
<slot></slot>`;

customElements.define('ropi-radio-group', RopiRadioGroupElement);

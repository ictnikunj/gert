import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';
import './checkbox-group-element.js?v=1637255330';

export default class RopiCheckboxGroupElement extends RopiHTMLElement {

  connectedCallback() {
    this.setAttribute('role', this.getAttribute('role') || 'group');
  }

  uncheckAll() {
    let checkboxElements = this.querySelectorAll('ropi-checkbox:not([disabled])');
    for (let checkboxElement of checkboxElements) {
      checkboxElement.checked = false;
    }
  }

  checkAll() {
    let checkboxElements = this.querySelectorAll('ropi-checkbox:not([disabled])');
    for (let checkboxElement of checkboxElements) {
      checkboxElement.checked = true;
    }
  }

  disableAll() {
    let checkboxElements = this.querySelectorAll('ropi-checkbox');
    for (let checkboxElement of checkboxElements) {
      checkboxElement.setAttribute('disabled', '');
    }
  }

  enableAll() {
    let checkboxElements = this.querySelectorAll('ropi-checkbox');
    for (let checkboxElement of checkboxElements) {
      checkboxElement.removeAttribute('disabled');
    }
  }

  get checkedValues() {
    let checkboxElements = this.querySelectorAll('ropi-checkbox:not([disabled])');
    let values = [];

    for (let checkboxElement of checkboxElements) {
      if (checkboxElement.checked) {
        values.push(checkboxElement.value);
      }
    }

    return values;
  }

  set checkedValues(values) {
    let checkboxElements = this.querySelectorAll('ropi-checkbox:not([disabled])');

    for (let checkboxElement of checkboxElements) {
      if (values.includes(String(checkboxElement.value == null ? '' : checkboxElement.value))) {
        checkboxElement.checked = true;
      } else {
        checkboxElement.checked = false;
      }
    }
  }
}

RopiCheckboxGroupElement._template = html`
<style>
  :host {
    display: block;
  }
</style>
<slot></slot>`;

customElements.define('ropi-checkbox-group', RopiCheckboxGroupElement);

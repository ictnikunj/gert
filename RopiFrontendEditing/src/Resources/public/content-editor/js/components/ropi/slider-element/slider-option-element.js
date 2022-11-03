import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';

export default class RopiSliderOptionElement extends RopiHTMLElement {

  connectedCallback() {
    this.setAttribute('role', this.getAttribute('role') || 'presentation');
  }

  set selected(selected) {
    this._selected = selected ? true : false;
  }

  get selected() {
    return this._selected ? true : false;
  }
}

customElements.define('ropi-slider-option', RopiSliderOptionElement);

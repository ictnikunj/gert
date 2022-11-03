import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';
import Draggable from '../draggable/draggable.js?v=1637255330';

import '../touchable-element/touchable-element.js?v=1637255330';

export default class RopiDraggableElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['disabled'];
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (this.hasAttribute('disabled')) {
      this.shadowRoot.querySelector('ropi-touchable').setAttribute('disabled', '');
      this.draggable.disable();
    } else {
      this.shadowRoot.querySelector('ropi-touchable').removeAttribute('disabled');
      this.draggable.enable();
    }
  }

  constructor() {
    super();

    this._draggable = new Draggable(this);
  }

  get draggable() {
    return this._draggable;
  }
}

RopiDraggableElement._template = html`
<style>
:host {
  display: block;
  user-select: none;
  -moz-user-select: none;
}

ropi-touchable {
  cursor: inherit;
}
</style>
<ropi-touchable>
  <slot></slot>
</ropi-touchable>`;

customElements.define('ropi-draggable', RopiDraggableElement);

import TypeUtil from '../type-util/type-util.js?v=1637255330';

export default class extends HTMLElement {

  constructor() {
    super();

    if (this.constructor._template instanceof HTMLTemplateElement) {
      if (this.shadowRoot === null) {
        this.attachShadow({mode: 'open'});

        this.shadowRoot.appendChild(
          this.constructor._template.content.cloneNode(true)
        );
      }
    }
  }

  dispatchEvent(event) {
    super.dispatchEvent(event);

    let on = "on" + event.type;

    if (TypeUtil.isFunction(this[on])) {
      this[on](event);
    }

    if (this.hasAttribute(on)) {
      new Function('event', this.getAttribute(on))(event);
    }
  }
}

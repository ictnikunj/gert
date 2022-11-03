import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';
import '../touchable-element/touchable-element.js?v=1637255330';

export default class RopiBreadcrumbItemElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['active', 'disabled']
  }

  attributeChangedCallback(name, valueBefore, value) {
      let container = this.shadowRoot.getElementById('container');
      let touchable = this.shadowRoot.querySelector('ropi-touchable');

      if (this.hasAttribute('disabled') || this.hasAttribute('active')) {
        touchable.setAttribute('disabled', '');
        this.setAttribute('aria-disabled', 'true');
      } else {
        touchable.removeAttribute('disabled');
        this.removeAttribute('aria-disabled');
      }
  }

  connectedCallback() {
    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'link');
    }

    this.attributeChangedCallback();
  }
}

RopiBreadcrumbItemElement._template = html`
<style>
  :host {
    display: inline-block;
    outline: none;
  }

  #container {
    padding: 0 var(--ropi-grid-outer-gutter-width, 1rem);
    display: inline-block;
    color: var(--ropi-color-font-75, grey);
  }

  :host([disabled]) #container {
    color: var(--ropi-color-font-50, darkgrey);
  }

  :host([active]) #container {
    color: var(--ropi-color-font-100, lightgrey);
  }

  #container::after {
    content: ">";
    position: absolute;
    left: -0.75rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: var(--ropi-color-font-50, darkgrey);
  }

  :host([noarrow]) #container::after {
    display: none;
  }

  ropi-touchable {
    display: inline-block;
    margin-right: 1rem;
  }

  :host(:last-child) ropi-touchable {
    margin-right: 0;
  }
</style>
<ropi-touchable>
  <div id="container">
    <slot></slot>
  </div>
</ropi-touchable>
`;

customElements.define('ropi-breadcrumb-item', RopiBreadcrumbItemElement);

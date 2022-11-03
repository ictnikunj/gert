import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiMenuElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['open'];
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (name === 'open') {
      this._removeListeners();

      if (this.hasAttribute('open')) {
        requestAnimationFrame(() => {
          this.addEventListener('click', this._clickHandler);
          window.addEventListener('click', this._windowClickHandler);
        });
      }
    }
  }

  constructor() {
    super();

    this._clickHandler = (event) => {
      event.stopPropagation();
    };

    this._windowClickHandler = (event) => {
      this.removeAttribute('open');
      this._removeListeners();
    };
  }

  disconnectedCallback() {
    this._removeListeners();
  }

  _removeListeners() {
    this.removeEventListener('click', this._clickHandler);
    window.removeEventListener('click', this._windowClickHandler);
  }
}

RopiMenuElement._template = html`
<style>
  :host {
    position: absolute;
    display: block;
    width: 100%;
    left: 0;
    background-color: var(--ropi-color-base, black);
    z-index: 30;
    max-width: 414px;
    border: solid 0.0625rem var(--ropi-color-contrast-medium, grey);
    transform: scale(0, 0);
    transform-origin: top left;
    transition: transform var(--ropi-animation-duration, 301ms) ease,
                opacity var(--ropi-animation-duration, 301ms) ease;
    opacity: 0;
    pointer-events: none;
    overflow: hidden;
    overflow-y: auto;
  }

  :host([open]) {
    transform: scale(1, 1);
    opacity: 1;
    pointer-events: all;
  }

  :host([type="action-left"]),
  :host([type="action-right"]) {
    top: 3rem;
    max-height: calc(90vh - 3rem);
    left: 0;
    right: auto;
    transform-origin: top left;
  }

  :host([type="action-right"]) {
    left: auto;
    right: 0;
    transform-origin: top right;
  }
</style>
<slot></slot>
`;

customElements.define('ropi-menu', RopiMenuElement);

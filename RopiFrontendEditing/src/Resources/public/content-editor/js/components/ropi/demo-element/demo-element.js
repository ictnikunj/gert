import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';
import '../code-element/code-element.js?v=1637255330';
import '../styles/styles.js?v=1637255330';

export default class RopiDemoElement extends RopiHTMLElement  {

  static get observedAttributes() {
    return ['sourcehidden', 'name'];
  }

  constructor() {
    super();

    this._slotchangeHandler = () => {
      this.update();
    }
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (value !== valueBefore) {
      this.update();
    }
  }

  connectedCallback() {
    this.update();

    this.shadowRoot.querySelector('slot').addEventListener(
      'slotchange',
      this._slotchangeHandler
    );
  }

  disconnectedCallback() {
    this.shadowRoot.querySelector('slot').removeEventListener(
      'slotchange',
      this._slotchangeHandler
    );
  }

  update() {
    let codeElement = this.shadowRoot.getElementById('code');
    let nameElement = this.shadowRoot.getElementById('name');

    if (this.hasAttribute('sourcehidden')) {
      codeElement.style.display = 'none';
    } else if (this.innerHTML !== codeElement.innerHTML) {
      codeElement.innerHTML = this.innerHTML;
    }

    if (this.hasAttribute('name')) {
      nameElement.innerText = this.getAttribute('name');
    }
  }
}

RopiDemoElement._template = html`
<style>
  :host {
    display: block;
    margin-bottom: 3.5rem;
  }

  #preview, h2 {
    padding: var(--ropi-grid-outer-gutter-width, 1rem);
  }

  h2 {
    background-color: var(--ropi-color-material-25, black);
    margin: 0;
    font-family: monospace;
    text-transform: uppercase;
    font-size: 1rem;
    font-weight: bold;
    color: var(--ropi-color-interactive, lightgreen);
  }

  ropi-code {
    padding: var(--ropi-grid-outer-gutter-width, 1rem);
    color: var(--ropi-color-font-50, grey);
    background-color: var(--ropi-color-material-25, black);
  }
</style>
<div>
  <h2 id="name">Basic example</h2>
  <div id="preview">
    <slot></slot>
  </div>
  <ropi-code id="code"></ropi-code>
</div>`;

customElements.define('ropi-demo', RopiDemoElement);

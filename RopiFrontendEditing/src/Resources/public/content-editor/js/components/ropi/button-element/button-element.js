import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';
import '../touchable-element/touchable-element.js?v=1637255330';
import '../oneliner-element/oneliner-element.js?v=1637255330';
import '../loading-element/loading-element.js?v=1637255330';

export default class RopiButtonElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['disabled', 'focusable'];
  }

  attributeChangedCallback(name, valueBefore, value) {
    let touchable = this.shadowRoot.querySelector('ropi-touchable');
    let button = this.shadowRoot.getElementById('button');

    if (this.hasAttribute('disabled')) {
      touchable.setAttribute('disabled', '');
    } else {
      touchable.removeAttribute('disabled');
    }

    if (this.hasAttribute('focusable') && !this.hasAttribute('disabled')) {
      touchable.setAttribute('tabindex', '0');
    } else {
      touchable.removeAttribute('tabindex');
    }
  }
}

RopiButtonElement._template = html`
<style>
  :host {
    background-color: var(--ropi-button-color, var(--ropi-color-interactive, blue));
    color: var(--ropi-color-white, white);
    display: block;
    text-align: center;
    white-space: nowrap;
    text-overflow: ellipsis;
    line-height: 3rem;
    height: 3rem;
    transition: background-color var(--ropi-animation-duration, 301ms) ease;
  }

  :host([nomaterial]) {
    background: none;
    color: var(--ropi-button-color, var(--ropi-color-interactive, blue));
  }

  :host([disabled]) {
    opacity: var(--ropi-disabled-opacity, 0.33);
  }
  
  ropi-loading {
      display: none;
      position: absolute;
      width: 100%;
      height: 100%;
      z-index: 0;
  }
  
  :host([loading]) ropi-loading {
      display: block;
  }
  
  :host([loading]), 
  :host([disabled]) {
      pointer-events: none;
  }

  #button {
    padding: 0 var(--ropi-button-gutter-width, var(--ropi-grid-outer-gutter-width, 1rem));
  }
  
  ropi-oneliner {
    position: relative;
  }
</style>
<ropi-touchable role="button">
  <ropi-loading loading></ropi-loading>
  <ropi-oneliner id="button">
    <slot></slot>
  </ropi-oneliner>
</ropi-touchable>
`;

customElements.define('ropi-button', RopiButtonElement);

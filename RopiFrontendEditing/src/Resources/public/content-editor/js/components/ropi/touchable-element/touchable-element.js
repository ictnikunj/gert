import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiTouchableElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['disabled'];
  }

  constructor() {
    super();

    this._glow = this.shadowRoot.getElementById('glow');
    this._wrapper = this.shadowRoot.getElementById('wrapper');

    this._touchendHandler = () => {
      document.removeEventListener('mouseup', this._touchendHandler);
    }

    this._clickHandler = () => {
      if (this.hasAttribute('disabled')) {
        return;
      }

      if (!this._preventBlur) {
        this.blur();
      }

      this._preventBlur = false;
    };

    this._touchstartHandler = () => {
      document.addEventListener('mouseup', this._touchendHandler);
    }

    this._keypressHandler = (event) => {
      if (event.keyCode === 13) {
        this._preventBlur = true;
        this.click();
      }
    }
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (this.hasAttribute('disabled')) {
      this.setAttribute('aria-disabled', 'true');
      //this.removeAttribute('tabindex');
    } else {
      this.removeAttribute('aria-disabled');
      //this.setAttribute('tabindex', '0');
    }
  }

  connectedCallback() {
    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'button');
    }

    this.attributeChangedCallback();

    this.addEventListener('touchstart', this._touchstartHandler, {passive: true});
    this.addEventListener('mousedown', this._touchstartHandler);
    this.addEventListener('touchend', this._touchendHandler);
    this.addEventListener('keypress', this._keypressHandler);
    this.addEventListener('click', this._clickHandler);
  }

  disconnectedCallback() {
    document.removeEventListener('mouseup', this._touchendHandler);

    this.removeEventListener('touchstart', this._touchstartHandler, {passive: true});
    this.removeEventListener('mousedown', this._touchstartHandler);
    this.removeEventListener('touchend', this._touchendHandler);
    this.removeEventListener('keypress', this._keypressHandler);
    this.removeEventListener('click', this._clickHandler);
  }
}

RopiTouchableElement._template = html`
<style>
  :host {
    position: relative;
    display: block;
    outline: none;
  }

  #wrapper {
    outline: none;
    border: none;
    -webkit-tap-highglow-color: rgba(255, 255, 255, 0);
    width: 100%;
    height: 100%;
  }

  :host([disabled]) {
    cursor: default;
  }

  #glow {
    content: " ";
    pointer-events: none;
    display: block;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: var(--ropi-touchable-glow-color, var(--ropi-color-font-75, white));
    opacity: 0;
    transition: opacity var(--ropi-animation-duration, 301ms) ease;
    z-index: 1;
  }

  :host(:focus) #glow,
  :host([focused]) #glow {
    transition-duration: 1ms;
    opacity: 0.17;
  }

  @media (hover: hover) {
   :host(:hover:not([disabled])) #glow {
     transition-duration: 1ms;
     opacity: 0.25;
   }
  }

  :host(:active:not([disabled])) #glow,
  :host([active]:not([disabled])) #glow {
    transition-duration: 444ms;
    opacity: 0.4;
  }

  :host([disabled]) #glow {
    transition: none;
  }
</style>
<div id="wrapper">
  <div id="glow"></div>
  <slot></slot>
</div>
`;

customElements.define('ropi-touchable', RopiTouchableElement);

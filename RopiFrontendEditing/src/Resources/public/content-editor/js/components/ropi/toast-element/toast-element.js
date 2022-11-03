import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiToastElement extends RopiHTMLElement {

    constructor() {
        super();

        this._box = this.shadowRoot.getElementById('box');

        this._timeoutHandler = () => {
            RopiToastElement._numElements--;

            if (this.parentNode) {
                this._box.animate([
                    {opacity: 0.9, transform: 'translateX(0)'},
                    {opacity: 0, transform: 'translateX(100%)'}
                ], {
                    duration: 301,
                    easing: 'ease',
                    fill: 'forwards'
                }).onfinish = () => {
                    if (this.parentNode) {
                        this.parentNode.removeChild(this);
                    }
                };
            }
        };
    }

    connectedCallback() {
        RopiToastElement._numElements++;

        let waitingTime = 0;

        if (this.hasAttribute('queued')) {
            waitingTime = 3602 * (RopiToastElement._numElements - 1);
        } else {
            if (RopiToastElement._lastTimeoutHandler) {
                RopiToastElement._lastTimeoutHandler();
            }
        }

        RopiToastElement._lastTimeoutHandler = this._timeoutHandler;

        this.style.zIndex = 2147480647 + RopiToastElement._numElements;

        setTimeout(() => {
            this._box.animate([
                {opacity: 0, transform: 'translateX(100%)'},
                {opacity: 0.9, transform: 'translateX(0)'}
            ], {
                duration: 301,
                easing: 'ease',
                fill: 'forwards'
            }).onfinish = () => {
                setTimeout(this._timeoutHandler, 3000);
            };
        }, waitingTime);
    }

    disconnectedCallback() {
        document.removeEventListener('mouseup', this._touchendHandler);
    }
}

RopiToastElement._numElements = 0;
RopiToastElement._template = html`
<style>
  :host {
    position: absolute;
    display: block;
    top: 3rem;
    right: 0;
    overflow: hidden;
    pointer-events: none;
  }
  
  #box {
    opacity: 0;
    transform: translateX(0);
    padding: var(--ropi-grid-outer-gutter-height, 1rem) var(--ropi-grid-outer-gutter-width, 1rem);
    background-color: var(--ropi-color-blue, lightskyblue);
    color: var(--ropi-color-white, white);
  }
  
  :host([severity="success"]) #box {
    background-color: var(--ropi-color-green, green);
  }
  
  :host([severity="error"]) #box {
    background-color: var(--ropi-color-error, red);
  }
</style>
<div id="box">
  <slot></slot>
</div>
`;

customElements.define('ropi-toast', RopiToastElement);

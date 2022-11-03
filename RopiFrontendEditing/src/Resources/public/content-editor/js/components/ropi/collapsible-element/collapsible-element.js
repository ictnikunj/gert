import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

import '../material-icon-element/material-icon-element.js?v=1637255330';
import '../touchable-element/touchable-element.js?v=1637255330';

export default class RopiCollapsibleElement extends RopiHTMLElement {

    constructor() {
        super();

        this._header = this.shadowRoot.getElementById('header');
        this._content = this.shadowRoot.getElementById('content');

        this._headerClickHandler = () => {
            this._toggle(true);
        };
    }

    connectedCallback() {
        if (!this.hasAttribute('aria-expanded')) {
            this.setAttribute('aria-expanded', 'false');
        }

        this._header.addEventListener('click', this._headerClickHandler);
    }

    disconnectedCallback() {
        this._header.removeEventListener('click', this._headerClickHandler);
    }

    toggle() {
        this._toggle();
    }

    collapse() {
        this.setAttribute('aria-expanded', 'false');
    }

    expand() {
        this.setAttribute('aria-expanded', 'true');
    }

    _toggle(dispatchEvents) {
        if (this.getAttribute('aria-expanded') === 'true') {
            if (dispatchEvents) {
                this.dispatchEvent(new CustomEvent('collapse', {
                    bubbles: true
                }));
            }

            this.collapse();
        } else {
            if (dispatchEvents) {
                this.dispatchEvent(new CustomEvent('expand', {
                    bubbles: true
                }));
            }

            this.expand();
        }
    }
}

RopiCollapsibleElement._template = html`
<style>
  :host {
    display: block;
  }
  
  #content {
    height: 0;
    overflow: hidden;
    position: relative;
  }
  
  #content-inner {
    opacity: 0;
    transition: opacity var(--ropi-animation-duration, 301ms) ease;
  }
  
  :host([aria-expanded="true"]) #content {
    height: auto;
  }
  
  :host([aria-expanded="true"]) #content-inner {
    opacity: 1;
  }
  
  #expand-icon {
    transition: transform var(--ropi-animation-duration, 301ms) ease;
    transform: rotateZ(0) translateY(-50%);
    transform-origin: top;
    position: absolute;
    right: var(--ropi-grid-outer-gutter-width);
    top: 50%;
  }
  
  :host([aria-expanded="true"]) #expand-icon {
    transform: rotateZ(180deg) translateY(-50%);
  }
</style>
<ropi-touchable id="header" role="presentation">
    <div id="header-inner">
        <slot name="header"></slot>
        <ropi-material-icon id="expand-icon">expand_more</ropi-material-icon>
    </div>
</ropi-touchable>
<div id="content">
    <div id="content-inner">
        <slot name="content"></slot>
    </div>
</div>
`;

customElements.define('ropi-collapsible', RopiCollapsibleElement);

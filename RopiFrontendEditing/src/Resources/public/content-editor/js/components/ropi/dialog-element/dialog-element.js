import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

import '../button-element/button-element.js?v=1637255330';
import '../subheader-element/subheader-element.js?v=1637255330';
import animation from '../styles/animation.js?v=1637255330';
import '../toast-element/toast-element.js?v=1637255330';

export default class RopiDialogElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['open'];
  }

  static get ACTION_PRIMARY() {
    return 'primary';
  }

  static get ACTION_SECONDARY() {
    return 'secondary';
  }

  static get ACTION_CANCEL() {
    return 'cancel';
  }

  constructor() {
    super();

    this._slotSecondary = this.shadowRoot.querySelector('slot[name="secondary"]');
    this._slotCancel = this.shadowRoot.querySelector('slot[name="cancel"]');
    this._buttonPrimary = this.shadowRoot.getElementById('primary');
    this._buttonSecondary = this.shadowRoot.getElementById('secondary');
    this._buttonCancel = this.shadowRoot.getElementById('cancel');
    this._layer = this.shadowRoot.getElementById('layer');
    this._dialog = this.shadowRoot.querySelector('[role="dialog"]');
    this._dialogContainer = this.shadowRoot.getElementById('dialog-container');

    this._slotchangeHandler = () => {
      this.update();
    };

    this._buttonClickHandler = (event) => {
      this._close(event.currentTarget.id);
    };

    this._keypressHandler = (event) => {
      if (event.keyCode === 13) {
        // Enter
        this._close(RopiDialogElement.ACTION_PRIMARY);
      }
    };
  }

  _close(action) {
    let closeEvent = new CustomEvent('dialogclose', {
      detail: {
        action: action
      },
      cancelable: true
    });

    this.dispatchEvent(closeEvent);

    if (closeEvent.defaultPrevented) {
      return;
    }

    this.removeAttribute('open');
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (name === 'open') {
      if (this.hasAttribute('open')) {
        document.body.overflow = 'hidden';
        document.body.pointerEvents = 'none';

        this._dialogContainer.style.display = 'flex';
        this._layer.style.display = 'block';

        this._layer.animate([
          {opacity: 0},
          {opacity: 0.66}
        ], {
          duration: animation.DURATION,
          easing: 'ease',
          fill: 'forwards'
        });

        let from = {transform: 'scale(1.5)', opacity: 0};
        let to = {transform: 'scale(1)', opacity: 1};

        this._dialog.animate([
          from,
          to
        ], {
          duration: animation.DURATION,
          easing: 'ease',
          fill: 'forwards'
        }).onfinish = () => {
          this._dialogContainer.style.pointerEvents = 'all';

          this.dispatchEvent(new CustomEvent('dialogopencomplete'));
        };
      } else {
        this._dialogContainer.style.pointerEvents = 'none';

        let dialogStyle = getComputedStyle(this._dialog);
        let layerStyle = getComputedStyle(this._layer);

        this._layer.animate([
          {opacity: layerStyle.opacity},
          {opacity: 0}
        ], {
          duration: animation.DURATION,
          easing: 'ease',
          fill: 'forwards'
        }).onfinish = () => {
          this._layer.style.display = 'none';
          this.dispatchEvent(new CustomEvent('dialogclosecomplete'));
        }

        let from = {transform: dialogStyle.transform, opacity: dialogStyle.opacity};
        let to = {transform: 'scale(0.75)', opacity: 0};

        this._dialog.animate([
          from,
          to
        ], {
          duration: animation.DURATION,
          easing: 'ease',
          fill: 'forwards'
        }).onfinish = () => {
          document.body.overflow = '';
          document.body.pointerEvents = '';
          this._dialogContainer.style.display = 'none';
        }
      }
    }
  }

  connectedCallback() {
    this._slotSecondary.addEventListener('slotchange', this._slotchangeHandler);
    this._slotCancel.addEventListener('slotchange', this._slotchangeHandler);
    this._buttonPrimary.addEventListener('click', this._buttonClickHandler);
    this._buttonSecondary.addEventListener('click', this._buttonClickHandler);
    this._buttonCancel.addEventListener('click', this._buttonClickHandler);
    this.addEventListener('keypress', this._keypressHandler);

    this.update();
  }

  disconnectedCallback() {
    this._slotSecondary.removeEventListener('slotchange', this._slotchangeHandler);
    this._slotCancel.removeEventListener('slotchange', this._slotchangeHandler);
    this._buttonPrimary.removeEventListener('click', this._buttonClickHandler);
    this._buttonSecondary.removeEventListener('click', this._buttonClickHandler);
    this._buttonCancel.removeEventListener('click', this._buttonClickHandler);
    this.removeEventListener('keypress', this._keypressHandler);
  }

  update() {
    if (this._slotSecondary.assignedNodes().length > 0) {
      this._buttonSecondary.style.display = '';
    } else {
      this._buttonSecondary.style.display = 'none';
    }

    if (this._slotCancel.assignedNodes().length > 0) {
      this._buttonCancel.style.display = '';
    } else {
      this._buttonCancel.style.display = 'none';
    }
  }

  pushError(message) {
    let toast = document.createElement('ropi-toast');
    toast.setAttribute('severity', 'error');
    toast.innerText = message;

    this.shadowRoot.querySelector('[role="dialog"]').appendChild(toast);
  }
}

RopiDialogElement._template = html`
<style>
:host {
  display: block;
}

#layer,
#dialog-container {
  display: none;
  pointer-events: none;
  position: fixed;
  width: 100vw;
  height: 100vh;
  left: 0;
  top: 0;
  z-index: 2147483647;
}

#dialog-container {
  justify-content: center;
  align-items: center;
  position: fixed;
}

#layer {
  opacity: 0;
  background-color: var(--ropi-color-black, black);
}

:host([open]) #layer {
  pointer-events: all;
}

:host([hidelayer]) #layer {
  visibility: hidden;
}

[role="dialog"] {
  max-width: 640px;
  max-height: calc(100vh - 3rem);
  background-color: var(--ropi-color-base, white);
  z-index: 2147483647;
  border: solid 0.0625rem var(--ropi-color-contrast-medium, grey);
  
  display: grid;
  grid-template-rows: min-content auto min-content;
}

:host([hide]) [role="dialog"] {
  display: none !important;
}

#action-panel {
  display: flex;
  flex-direction: row;
  float: right;
}

#action-panel::after {
  content: " ";
  clear: both;
  width: 0;
  height: 0;
}

#content {
  padding: var(--ropi-grid-outer-gutter-height, 0.75rem)
           var(--ropi-grid-outer-gutter-width, 1rem);
  padding-top: 0;
  margin: 0;
}

:host([nopadding]) #content {
  padding-left: 0;
  padding-right: 0;
}

:host([fullwidth]) [role="dialog"] {
  width: calc(100vw - 2rem);
}

#content {
  position: relative;
  overflow: auto;
}

:host([fullheight]) [role="dialog"] {
  height: calc(100vh - 3rem);
}

:host([noscroll]) #content {
  overflow: visible;
}

#action-panel-container::after {
  content: " ";
  display: table;
  clear: both;
}
</style>
<div id="layer"></div>
<div id="dialog-container">
  <div role="dialog" aria-labelledby="title" aria-describedby="content">
    <ropi-subheader id="title">
      <slot name="title"></slot>
    </ropi-subheader>
    <div id="content">
      <slot name="content"></slot>
    </div>
    <div id="action-panel-container">
      <div id="action-panel">
        <ropi-button nomaterial id="cancel"><slot name="cancel"></slot></ropi-button>
        <ropi-button nomaterial id="secondary"><slot name="secondary"></slot></ropi-button>
        <ropi-button nomaterial id="primary"><slot name="primary">Ok</slot></ropi-button>
      </div>
    </div>
  </div>
</div>
`;

customElements.define('ropi-dialog', RopiDialogElement);

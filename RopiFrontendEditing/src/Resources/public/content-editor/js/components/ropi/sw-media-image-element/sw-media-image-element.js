import RopiHTMLInputProxyElement from '../html-input-proxy-element/html-input-proxy-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

import TranslateElement from '../translate-element/translate-element.js?v=1637255330';
import TypeUtil from '../type-util/type-util.js?v=1637255330';

import '../dialog-element/dialog-element.js?v=1637255330';
import '../touchable-element/touchable-element.js?v=1637255330';
import '../button-element/button-element.js?v=1637255330';
import '../image-box-element/image-box-element.js?v=1637255330';
import '../material-icon-element/material-icon-element.js?v=1637255330';
import '../sw-media-manager-element/sw-media-manager-element.js?v=1637255330';

TranslateElement.registerSnippets({
  ropiSwMediaImage: {
    reset: 'Reset'
  }
});

export default class RopiSwMediaImageElement extends RopiHTMLInputProxyElement {

  static get observedAttributes() {
    return RopiHTMLInputProxyElement.observedAttributes.concat([
      'apibaseuri',
      'accept',
      'value'
    ]);
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (name === 'apibaseuri') {
      this._mediaManager.setAttribute('apibaseuri', value);
      return;
    }

    if (name === 'accept') {
      this._mediaManager.setAttribute('accept', value);
      return;
    }

    if (name === 'value') {
      let parsedValue = null;

      try {
        parsedValue = JSON.parse(value);
      } catch (e) {
        // Fail silently
      }

      if (TypeUtil.isObject(parsedValue)) {
        this.value = parsedValue;
      } else {
        this.value = null;
      }

      return;
    }

    super.attributeChangedCallback(name, valueBefore, value);
  }

  constructor() {
    super();

    this._input = this.shadowRoot.getElementById('input');
    this._dialog = this.shadowRoot.getElementById('dialog');
    this._touchablePreview = this.shadowRoot.getElementById('touchable-preview');
    this._resetButton = this.shadowRoot.getElementById('reset-button');
    this._imageBoxPreview = this.shadowRoot.getElementById('image-box-preview');
    this._mediaManager = this.shadowRoot.querySelector('ropi-sw-media-manager');

    this._clickHandler = () => {
      document.body.appendChild(this._dialog);

      this._dialog.setAttribute('open', '');

      this._mediaManager.load();
    };

    this._dialogCloseHandler = () => {
      this._mediaManager.reset();
    };

    this._dialogCloseCompleteHandler = () => {
      this.shadowRoot.appendChild(this._dialog);
    };

    this._resetButtonClickHandler = () => {
      this._setValue('', true);
    };

    this._mediaManagerSelectHandler = (event) => {
      this._setValue({
        id: event.detail.media.id,
        url: event.detail.media.url
      }, true, true);

      this._imageBoxPreview.setAttribute('src', event.detail.media.url);

      this._dialog.removeAttribute('open');
    };
  }

  connectedCallback() {
    this._resetButton.addEventListener('click', this._resetButtonClickHandler);
    this._touchablePreview.addEventListener('click', this._clickHandler);
    this._mediaManager.addEventListener('select', this._mediaManagerSelectHandler);
    this._dialog.addEventListener('dialogclose', this._dialogCloseHandler);
    this._dialog.addEventListener('dialogclosecomplete', this._dialogCloseCompleteHandler);
  }

  update() {
    this._imageBoxPreview.removeAttribute('src');

    if (!this.value || !this.value.url) {
      return;
    }

    this._imageBoxPreview.setAttribute('src', this.value.url);
  }

  _setValue(value, dispatchEvents, noUpdate) {
    if (!TypeUtil.isObject(value)) {
      value = null;
    }

    this._value = value;

    if (!noUpdate) {
      this.update();
    }

    if (dispatchEvents) {
      this.dispatchEvent(new CustomEvent('change', {
        bubbles: true
      }));
    }
  }

  set value(value) {
    this._setValue(value);
  }

  get value() {
    return this._value || null;
  }

}

RopiSwMediaImageElement._template = html`
<style>
  @keyframes ropi-sw-media-image-element-loading {
    0% {
      transform: rotateZ(0);
    }
    50% {
      transform: rotateZ(180deg) scale(0.5);
    }
    100% {
      transform: rotateZ(360deg);
    }
  }

  :host {
    display: block;
    padding: var(--ropi-grid-outer-gutter, 0.75rem 1rem);
  }

  .wrap {
    border: solid 1px var(--ropi-material-50, grey);
    transform: translateZ(0);
  }

  .container {
    position: relative;
    height: 128px;
  }

  #touchable-preview {
    position: absolute;
    height: 100%;
    width: 100%;
  }

  ropi-image-box {
    height: 100%;
    width: 128px;
    margin: 0 auto;
    background: none;
  }

  ropi-image-box > ropi-material-icon {
    display: block;
    width: 100%;
    height: 100%;
    color: var(--ropi-color-base-contrast-medium, black);
    position: absolute;
    left: 0;
    top: 0;
  }

  ropi-image-box[loading] > ropi-material-icon {
    opacity: 0.5;
    animation: ropi-sw-media-image-element-loading 1.5s ease infinite;
  }

  ropi-image-box[loaded] > ropi-material-icon {
    display: none;
  }

  ropi-touchable[disabled] {
    opacity: var(--ropi-disabled-opacity, 0.33);
  }
</style>
<div class="wrap">
  <div class="container">
    <ropi-touchable id="touchable-preview">
      <ropi-image-box id="image-box-preview" nolazyloading>
        <ropi-material-icon>insert_photo</ropi-material-icon>
      </ropi-image-box>
    </ropi-touchable>
  </div>
</div>
<ropi-button id="reset-button">
  <ropi-translate>ropiSwMediaImage.reset</ropi-translate>
</ropi-button>
<input id="input" type="hidden" />
<ropi-dialog id="dialog" fullwidth fullheight nopadding noscroll>
  <div slot="title">
    <ropi-translate>Media Manager</ropi-translate>
  </div>
  <div slot="content">
    <ropi-sw-media-manager></ropi-sw-media-manager>
  </div>
  <div slot="primary">
    <ropi-translate>Close</ropi-translate>
  </div>
</ropi-dialog>
`;

customElements.define('ropi-sw-media-image', RopiSwMediaImageElement);

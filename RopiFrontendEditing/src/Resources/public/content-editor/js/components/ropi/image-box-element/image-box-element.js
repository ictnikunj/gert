import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

import DOMUtil from '../dom-util/dom-util.js?v=1637255330';

export default class RopiImageBoxElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['src'];
  }

  constructor() {
    super();

    this._reset();

    this._intersectionHandler = (entries, observer) => {
      for (let entry of entries) {
        if (entry.isIntersecting) {
          this._stopIntersectionObserver();
          this._load();
        }
      }
    };

    this._imageboxloadHandler = (event) => {
      if (event.detail.src === this.getAttribute('src')) {
        this._stopIntersectionObserver();
        this._load();
      }
    };
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (name === 'src') {
      this._reset();
      this._initIntersectionObserver();

      if (this.hasAttribute('nolazyloading')) {
        this._load();
      }
    }
  }

  connectedCallback() {
    document.addEventListener('imageboxload', this._imageboxloadHandler);
  }

  disconnectedCallback() {
    this._stopIntersectionObserver();
    document.removeEventListener('imageboxload', this._imageboxloadHandler);
  }

  _reset() {
    this._loading = false;
    this._loaded = false;
  }

  _initIntersectionObserver() {
    if (this.hasAttribute('nolazyloading')) {
      this._stopIntersectionObserver();
      return;
    }

    if (!this._observer) {
      this._observer = new IntersectionObserver(this._intersectionHandler, {
        root: DOMUtil.scrollParent(this),
        rootMargin: '512px',
        threshold: 0
      });

      this._observer.observe(this);
    }
  }

  _stopIntersectionObserver() {
    if (this._observer) {
      this._observer.disconnect();
      delete this._observer;
    }
  }

  _load() {
    if (this._loading || this._loaded) {
      return;
    }

    this.removeAttribute('loaded');

    let imageElement = this.shadowRoot.getElementById('image');

    let imageSource = this.getAttribute('src');
    if (!imageSource) {
      imageElement.style.backgroundImage = '';
      return;
    }

    this._loading = true;
    this.setAttribute('loading', '');

    imageElement.style.backgroundImage = 'url("' + imageSource + '")';

    let containerElement = this.shadowRoot.getElementById('container');

    let image = new Image();

    // TODO: fallback image on no internet connection and retry loading as soon as internet is available again
    image.onload = (e) => {
      this._loading = false;
      this._loaded = true;
      this.setAttribute('loaded', '');
      this.removeAttribute('loading');

      this.dispatchEvent(new CustomEvent('imageboxload', {
        bubbles: true,
        composed: true,
        detail: {
          src: imageSource
        }
      }));

      if (imageElement.style.opacity === '1') {
        return;
      }

      imageElement.animate([
        {opacity: '0'},
        {opacity: '1'}
      ], {
        duration: 301,
        easing: 'linear',
        fill: 'forwards'
      });
    };

    image.src = imageSource;

    if (image.complete) {
      imageElement.style.opacity = '1';
    }
  }
}

RopiImageBoxElement._template = html`
 <style>
   :host {
     display: block;
     position: relative;
     z-index: 0;
     white-space: normal;
     top: 0;
     left: 0;
     width: 100%;
     height: 100%;
     background-color: var(--ropi-color-material-50, dimgrey);
   }

   #container {
     position: inherit;
     z-index: 1;
     width: inherit;
     height: inherit;
   }

   #image {
     position: absolute;
     top: 0;
     left: 0;
     width: 100%;
     height: 100%;
     background-size: cover;
     background-position: center center;
     z-index: 0;
     opacity: 0;
   }
 </style>
 <div id="image"></div>
 <div id="container">
   <slot></slot>
 </div>
`;

customElements.define('ropi-image-box', RopiImageBoxElement);

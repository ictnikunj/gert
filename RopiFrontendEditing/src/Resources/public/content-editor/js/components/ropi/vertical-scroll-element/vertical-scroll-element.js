import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

import SmoothScroll from '../smooth-scroll/smooth-scroll.js?v=1637255330';
import '../touchable-element/touchable-element.js?v=1637255330';

export default class RopiVerticalScrollElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['nocontrols'];
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (name === 'nocontrols') {
      this._resizeHandler();
      requestAnimationFrame(() => {
        this.scrollPosition = 0;
      });
    }
  }

  constructor() {
    super();

    this._container = this.shadowRoot.getElementById('container');
    this._left = this.shadowRoot.getElementById('left');
    this._right = this.shadowRoot.getElementById('right');
    this._slot = this.shadowRoot.querySelector('slot');

    this._resizeHandler = () => {
      this._updateArrows();

      if (this.scrollPosition > this.maxScrollPosition) {
        this.scrollPosition = this.maxScrollPosition;
      }
    };

    this._slotchangeHandler = () => {
      this._updateArrows();
    };

    this._arrowMouseDownHandler = (event) => {
      if (event.cancelable) {
        event.preventDefault();
      }

      if (event.currentTarget.hasAttribute('disabled')) {
        event.currentTarget.removeAttribute('active');
        return;
      }

      event.currentTarget.setAttribute('active', '');

      this._scrollDelta = event.currentTarget.id === 'left'
                          ? -8
                          : 8;

      let scroll = () => {
        this.scrollPosition += this._scrollDelta;
        this._animationFrame = requestAnimationFrame(scroll);
      };

      if (this._animationFrame) {
        cancelAnimationFrame(this._animationFrame);
        delete this._animationFrame;
      }

      if (this._timeout) {
        clearTimeout(this._timeout);
        delete this._timeout;
      }

      this._timeout = setTimeout(() => {
        this._animationFrame = requestAnimationFrame(scroll);
      }, 300);

      if (!this._locked) {
        this._locked = true;
        let newPosition = this.scrollPosition + this._scrollDelta * 10;
        this.scrollSmooth(null, newPosition).then(() => {
          this._locked = false;
        });
      }
    };

    this._mouseUpHandler = (event) => {
      let activeTouchable = this.shadowRoot.querySelector('ropi-touchable[active]');
      if (activeTouchable) {
        activeTouchable.removeAttribute('active');
      }

      if (this._timeout) {
        clearTimeout(this._timeout);
        delete this._timeout;
      }

      if (this._animationFrame) {
        cancelAnimationFrame(this._animationFrame);
        delete this._animationFrame;
      }
    };

    this._scrollHandler = () => {
      this._updateArrows();
    };

    this._wheelHandler = (event) => {
      let x = Math.ceil(Math.abs(event.deltaX));

      if (x || this._wheelX) {
        this._wheelX = true;

        if (this._wheelXTimeout) {
          clearTimeout(this._wheelXTimeout);
        }

        this._wheelXTimeout = setTimeout(() => {
          this._wheelX = false;
          delete this._wheelXTimeout;
        }, 777);

        return;
      }

      let y = Math.ceil(Math.abs(event.deltaY));
      if (y) {
        if (event.deltaMode === WheelEvent.DOM_DELTA_PIXEL) {
          event.preventDefault();
          this.scrollPosition += Math.ceil(event.deltaY);
        } else {
          event.preventDefault();
          this.scrollPosition += event.deltaY > 0 ? 50 : -50;
        }
      }
    };
  }

  _updateArrows(currentScroll) {
    //  this.removeAttribute('nocontrols');

      currentScroll = Math.ceil(
                        currentScroll == null
                        ? this.scrollPosition
                        : currentScroll
                      );

      let maxScroll = this.maxScrollPosition;
      let treshold = 1;
      if (Math.ceil(currentScroll + treshold) >= maxScroll) {
        this._right.setAttribute('disabled', '');
      } else {
        this._right.removeAttribute('disabled');
      }

      if (currentScroll <= 0) {
        this._left.setAttribute('disabled', '');
      } else {
        this._left.removeAttribute('disabled');
      }

      if (this._left.hasAttribute('disabled')
          && this._right.hasAttribute('disabled')) {
            //this.setAttribute('nocontrols', '');
      }
  }

  connectedCallback() {
    window.addEventListener('resize', this._resizeHandler);
    this._left.addEventListener('mousedown', this._arrowMouseDownHandler);
    this._right.addEventListener('mousedown', this._arrowMouseDownHandler);
    this._left.addEventListener('touchstart', this._arrowMouseDownHandler, {passive: false});
    this._right.addEventListener('touchstart', this._arrowMouseDownHandler, {passive: false});
    this.addEventListener('mouseup', this._mouseUpHandler);
    this.addEventListener('touchend', this._mouseUpHandler);
    this._container.addEventListener('scroll', this._scrollHandler);
    this._slot.addEventListener('slotchange', this._slotchangeHandler);
    this.addEventListener('wheel', this._wheelHandler, {passive: false});

    this._updateArrows();
  }

  disconnectedCallback() {
    window.removeEventListener('resize', this._resizeHandler);
    this._left.removeEventListener('mousedown', this._arrowMouseDownHandler);
    this._right.removeEventListener('mousedown', this._arrowMouseDownHandler);
    this._left.removeEventListener('touchstart', this._arrowMouseDownHandler, {passive: false});
    this._right.removeEventListener('touchstart', this._arrowMouseDownHandler, {passive: false});
    this.removeEventListener('mouseup', this._mouseUpHandler);
    this.removeEventListener('touchend', this._mouseUpHandler);
    this._container.removeEventListener('scroll', this._scrollHandler);
    this._slot.removeEventListener('slotchange', this._slotchangeHandler);
    this.removeEventListener('wheel', this._wheelHandler, {passive: false});
  }

  get scrollPosition() {
    return this._container.scrollLeft;
  }

  set scrollPosition(scrollPosition) {
    this._container.scrollLeft = scrollPosition;
  }

  get maxScrollPosition() {
    return this._container.scrollWidth - this._container.offsetWidth;
  }

  scrollSmooth(from, to) {
    return SmoothScroll.vertical(
      this._container,
      from,
      to
    );
  }
}

RopiVerticalScrollElement._template = html`
<style>
  :host{
    display: block;
    color: var(--ropi-color-font-75, lightgrey);
    height: 3rem;
    line-height: 3rem;
    position: relative;
    transform: translateZ(0);
    background-color: var(--ropi-color-base, black);
  }

  #container-wrap {
    width: 100%;
    height: 100%;
    overflow: hidden;
    position: relative;
    background-color: inherit;
  }

  ropi-touchable {
    position: absolute;
    width: 3rem;
    height: 100%;
    top: 0;
    left: 0;
    text-align: center;
    background-color: inherit;
    z-index: 10;
  }

  #right {
    left: auto;
    right: 0;
  }

  .arrow {
    display: inline-block;
    width: 0;
    height: 0;
    border-top: 0.3rem solid transparent;
    border-bottom: 0.3rem solid transparent;
    border-right: 0.3rem solid var(--ropi-color-font-75, lightgrey);
  }

  #right .arrow {
    width: 0;
    height: 0;
    border: none;
    border-top: 0.3rem solid transparent;
    border-bottom: 0.3rem solid transparent;
    border-left: 0.3rem solid var(--ropi-color-font-75, lightgrey);
  }

  ropi-touchable[disabled] .arrow {
    opacity: 0.33;
  }

  #container {
    height: 200%;
    position: absolute;
    left: 3rem;
    right: 3rem;
    overflow-x: scroll;
    -webkit-overflow-scrolling: touch;
    white-space: nowrap;
  }

  :host([nocontrols]) #left,
  :host([nocontrols]) #right {
    display: none;
  }

  :host([nocontrols]) #container {
    left: 0;
    right: 0;
  }
</style>
<div id="container-wrap">
  <ropi-touchable id="left">
    <span class="arrow"></span>
  </ropi-touchable>
  <div id="container">
    <slot></slot>
  </div>
  <ropi-touchable id="right">
    <span class="arrow"></span>
  </ropi-touchable>
</div>
`;

customElements.define('ropi-vertical-scroll', RopiVerticalScrollElement);

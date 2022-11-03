import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import DOMUtil from '../dom-util/dom-util.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';
import animation from '../styles/animation.js?v=1637255330';
import './slider-option-element.js?v=1637255330';

export default class RopiSliderElement extends RopiHTMLElement {

  static get observedAttributes() {
    return [
      'disabled',
      'readonly'
    ];
  }

  constructor() {
    super();

    this._handleArea = this.shadowRoot.getElementById('handle-area');
    this._slide = this.shadowRoot.getElementById('slide');
    this._railArea = this.shadowRoot.getElementById('rail-area');
    this._handleFocus = this.shadowRoot.getElementById('handle-focus');
    this._tooltip = this.shadowRoot.getElementById('tooltip');

    this._focusHandler = () => {
      this._handleFocus.classList.remove('hidden');
      this._handleFocus.classList.add('visible');
    };

    this._blurHandler = () => {
      this._handleFocus.classList.remove('hidden');
      this._handleFocus.classList.remove('visible');
    };

    this._resizeHandler = () => {
      this._updateHandlePosition(true);
    };

    this._startHandler = (event) => {
      if (this.hasAttribute('disabled') || this.hasAttribute('readonly')) {
        return;
      }

      let x = event.type === 'mousedown'
              ? event.x
              : event.touches[0].clientX;

      this._moved = false;
      this._prevented = false;
      this._clickedPosition = x - this._railArea.getBoundingClientRect().x;

 	    this.addEventListener('touchmove', this._moveHandler);

      if (event.type === 'mousedown') {
   	    window.addEventListener('mousemove', this._moveHandler, {passive: true});
        window.addEventListener('mouseup', this._endHandler, {passive: true});
      } else {
        this._startScreenX = event.touches[0].screenX;
        this._startScreenY = event.touches[0].screenY;
      }
    };

    this._moveHandler = (event) => {
      if (event.type === 'touchmove') {
        if (!this._moved) {
          let screenX = event.touches[0].screenX;
          let screenY = event.touches[0].screenY;

          let deltaY = Math.abs(screenY - this._startScreenY);
          if (deltaY > 10) {
            this.removeEventListener('touchmove', this._moveHandler);
            this._prevented = true;
            return;
          }

          let deltaX = Math.abs(screenX - this._startScreenX);
          if (deltaX < 20) {
            return;
          }
        }

        event.preventDefault();
      }

      this._handleFocus.classList.remove('hidden');
      this._handleFocus.classList.add('visible');

      this._moved = true;

      let x = event.type === 'mousemove'
              ? event.x
              : event.touches[0].clientX;

      this._setHandlePosition(
        x - this._railArea.getBoundingClientRect().x,
        true
      );

      let stepElement = this._findClosestStepElementByPercentagePosition(
        this._slideScaleX * 100
      );

      if (stepElement) {
        this._updateValueProjections(stepElement);
      }
    };

    this._endHandler = (event) => {
      this.removeEventListener('touchmove', this._moveHandler);
      window.removeEventListener('mousemove', this._moveHandler, {passive: true});
      window.removeEventListener('mouseup', this._endHandler, {passive: true});

      if (this._prevented) {
        return;
      }

      if (!this._moved) {
        this._setHandlePosition(this._clickedPosition);
      }

      let stepElement = this._findClosestStepElementByPercentagePosition(
        this._slideScaleX * 100
      );

      if (!stepElement) {
        return;
      }

      if (stepElement._value === this.valueAsNumber) {
        this._updateHandlePosition();
      } else {
        this.value = stepElement._value;
        this.dispatchEvent(new CustomEvent('change', {
          bubbles: true
        }));
      }

      this._handleFocus.classList.add('hidden');
      this._handleFocus.classList.remove('visible');
    };

    this._keydownHandler = (event) => {
      if (this.hasAttribute('disabled') || this.hasAttribute('readonly')) {
        return;
      }

      this._handleFocus.classList.remove('hidden');
      this._handleFocus.classList.add('visible');

      if ([33, 38, 39].includes(event.keyCode)) {
        // Page Up 33
        // Up Arrow 38
        // Right Arrow 39
        event.preventDefault();

        let oldValue = this.valueAsNumber;
        this.value = this.valueAsNumber + 1;
        if (this.valueAsNumber !== oldValue) {
          this.dispatchEvent(new CustomEvent('change', {
            bubbles: true
          }));
        }

        return;
      }

      if ([34, 37, 40].includes(event.keyCode)) {
        // Page Down 34
        // Left Arrow 37
        // Down Arrow 40
        event.preventDefault();

        let oldValue = this.valueAsNumber;
        this.value = this.valueAsNumber - 1;
        if (this.valueAsNumber !== oldValue) {
          this.dispatchEvent(new CustomEvent('change', {
            bubbles: true
          }));
        }

        return;
      }

      if (event.keyCode === 35) {
        // End 35
        event.preventDefault();

        let oldValue = this.valueAsNumber;
        this.value = this._maxValue;
        if (this._maxValue !== oldValue) {
          this.dispatchEvent(new CustomEvent('change', {
            bubbles: true
          }));
        }

        return;
      }

      if (event.keyCode === 36) {
        // Home/Pos1 36
        event.preventDefault();

        let oldValue = this.valueAsNumber;
        this.value = this._minValue;
        if (this._minValue !== oldValue) {
          this.dispatchEvent(new CustomEvent('change', {
            bubbles: true
          }));
        }

        return;
      }
    };
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (this.hasAttribute('disabled')) {
      this.setAttribute('aria-disabled', 'true');
      //this.removeAttribute('tabindex');
    } else {
      this.removeAttribute('aria-disabled');
      //this.setAttribute('tabindex', this.getAttribute('tabindex') || 0);
    }

    if (name === 'readonly') {
      if (this.hasAttribute('readonly')) {
        this.setAttribute('aria-readonly', 'true');
      } else {
        this.removeAttribute('aria-readonly');
      }
    }
  }

  connectedCallback() {
    this.setAttribute('role', this.getAttribute('role') || 'slider');
    this.setAttribute('aria-orientation', this.getAttribute('aria-orientation') || 'horizontal');
    this.attributeChangedCallback();

    this._renderSteps();
    this._updateHandlePosition(true);
    this._updateValueProjections();

    this._observer = new MutationObserver(() => {
      this._renderSteps();
      this._updateHandlePosition();
    });

    this._observer.observe(this, {
      childList: true
    });

    this.shadowRoot.getElementById('rail-area').addEventListener('touchstart', this._startHandler, {passive: true});
    this.shadowRoot.getElementById('rail-area').addEventListener('mousedown', this._startHandler, {passive: true});
    this.shadowRoot.getElementById('rail-area').addEventListener('touchend', this._endHandler, {passive: true});

    this.addEventListener('keydown', this._keydownHandler);
    this.addEventListener('focus', this._focusHandler);
    this.addEventListener('blur', this._blurHandler);

    window.addEventListener('resize', this._resizeHandler);
  }

  disconnectedCallback() {
    if (this._observer) {
      this._observer.disconnect();
      delete this._observer;
    }

    this.shadowRoot.getElementById('rail-area').removeEventListener('touchstart', this._startHandler, {passive: true});
    this.shadowRoot.getElementById('rail-area').removeEventListener('mousedown', this._startHandler, {passive: true});
    this.shadowRoot.getElementById('rail-area').removeEventListener('touchend', this._endHandler, {passive: true});

    this.removeEventListener('keydown', this._keydownHandler);
    this.removeEventListener('focus', this._focusHandler);
    this.removeEventListener('blur', this._blurHandler);

    window.removeEventListener('resize', this._resizeHandler);
  }

  set value(value) {
    value = parseInt(value, 10);

    if (isNaN(value)) {
      return;
    }

    if (value < this._minValue) {
      value = this._minValue;
    } else if (value > this._maxValue) {
      value = this._maxValue;
    }

    let stepElements = this.shadowRoot.querySelectorAll('.step');

    for (let stepElement of stepElements) {
      if (stepElement._value === value) {
        stepElement._relatedOptionElement.selected = true;
        this._updateValueProjections(stepElement);
      } else {
        stepElement._relatedOptionElement.selected = false;
      }
    }

    this._value = String(value);

    this._updateHandlePosition();
  }

  get value() {
    if (this._value == null) {
      return this.defaultValue;
    }

    let stepElements = this.shadowRoot.querySelectorAll('.step');

    for (let stepElement of stepElements) {
      if (stepElement._relatedOptionElement.selected) {
        return String(stepElement._value);
      }
    }

    if (stepElements.length > 0) {
      return String(stepElements[0]._value);
    }

    return '0';
  }

  get valueAsNumber() {
    return parseInt(this.value, 10);
  }

  stepUp(steps) {
    this.value = this.valueAsNumber + steps;
  }

  stepDown(steps) {
    this.value = this.valueAsNumber - steps;
  }

  _updateValueProjections(stepElement) {
    stepElement = stepElement || this._findStepElementByValue(this.valueAsNumber);
    if (!stepElement) {
      return;
    }

    this.setAttribute('aria-valuenow', stepElement._value);
    this.setAttribute('aria-valuetext', stepElement._relatedOptionElement.textContent);

    let valueTextTarget = this.valueTextTarget;
    if (valueTextTarget) {
      valueTextTarget.textContent = stepElement._relatedOptionElement.textContent;
    }
  }

  _updateHandlePosition(noAnimation) {
    let stepElement = this._findStepElementByValue(this.valueAsNumber);

    if (!stepElement) {
      stepElement = this.shadowRoot.querySelector('.step');
      if (!stepElement) {
        return;
      }
    }

    this._setHandlePosition(stepElement.offsetLeft, noAnimation);
  }

  _setHandlePosition(newHandlePosition, noAnimation) {
    let railAreaRect = this._railArea.getBoundingClientRect();

    if (newHandlePosition < 0) {
      newHandlePosition = 0;
    } else if (newHandlePosition > railAreaRect.width) {
      newHandlePosition = railAreaRect.width;
    }

    let newSlideScaleX = 1 / railAreaRect.width * newHandlePosition;
    if (newSlideScaleX <= 0) {
      // Workaround: Animation with scale(0) is buggy in Chrome
      newSlideScaleX = 0.0001;
    }

    let handleAreaStyle,
        slideStyle;

    if (!noAnimation) {
      handleAreaStyle = getComputedStyle(this._handleArea);
      slideStyle = getComputedStyle(this._slide);
    }

    if (noAnimation) {
      this._handleArea.style.transform = `translateX(${newHandlePosition}px)`;
      this._slide.style.transform = `scaleX(${newSlideScaleX})`;
    } else {
      this._handleAreaAnimation = this._handleArea.animate([
        {transform: handleAreaStyle.transform},
        {transform: `translateX(${newHandlePosition}px)`}
      ], {
        duration: animation.DURATION,
        easing: 'ease'
      });

      this._handleAreaAnimation.onfinish = () => {
        this._handleArea.style.transform = `translateX(${newHandlePosition}px)`;
      };

      this._slideAnimation = this._slide.animate([
        {transform: slideStyle.transform},
        {transform: `scaleX(${newSlideScaleX})`}
      ], {
        duration: animation.DURATION,
        easing: 'ease'
      });

      this._slideAnimation.onfinish = () => {
        this._slide.style.transform = `scaleX(${newSlideScaleX})`;
      };
    }

    this._handlePosition = newHandlePosition;
    this._slideScaleX = newSlideScaleX;
  }

  _renderSteps() {
    let stepsElement = this.shadowRoot.getElementById('steps');
    let optionElements = this.querySelectorAll('ropi-slider-option');
    let percentPerStep = 100 / (optionElements.length - 1);
    let fragment = document.createDocumentFragment();

    for (let i = 0; i < optionElements.length; i++) {
      let optionElement = optionElements[i];
      let optionPercent = (i * percentPerStep);

      let stepElement = document.createElement('div');
      stepElement.classList.add('step');

      if (optionPercent === 100) {
        stepElement.style.right = '0';
        this._maxValue = i;
        this.setAttribute('aria-valuemax', i);
      } else {
        stepElement.style.left = optionPercent + '%';

        if (optionPercent === 0) {
          this._minValue = i;
          this.setAttribute('aria-valuemin', i);
        }
      }

      stepElement._value = i;
      stepElement._relatedOptionElement = optionElement;

      fragment.appendChild(stepElement);
    }

    stepsElement.textContent = '';
    stepsElement.appendChild(fragment);
  }

  _findClosestStepElementByPercentagePosition(percentagePosition) {
    percentagePosition = percentagePosition > 100
                         ? 100
                         : (percentagePosition < 0) ? 0 : percentagePosition;

    let stepElements = this.shadowRoot.querySelectorAll('.step');
    let lastDeltaX = -1;
    let nearestStepElement;

    for (let stepElement of stepElements) {
      let optionElement = stepElement._relatedOptionElement;
      if (!optionElement) {
        continue;
      }

      let stepPercentagePosition = this._percentagePositionFromStepElement(stepElement);

      let optionDeltaX = Math.abs(stepPercentagePosition - percentagePosition);
      if (lastDeltaX === -1 || optionDeltaX < lastDeltaX) {
        lastDeltaX = optionDeltaX;
        nearestStepElement = stepElement;
      }
    }

    return nearestStepElement;
  }

  _findStepElementByValue(value) {
    let stepElements = this.shadowRoot.querySelectorAll('.step');

    for (let stepElement of stepElements) {
      if (stepElement._value === value) {
        return stepElement;
      }
    }

    return null;
  }

  _percentagePositionFromStepElement(stepElement) {
    let percentagePosition = parseFloat(stepElement.style.left);
    if (isNaN(percentagePosition)) {
      // Style property "left" is not set for the last step element, because
      // the last step element has property "right" set to 0 instead
      percentagePosition = 100;
    }

    return percentagePosition;
  }

  get name() {
    return this.hasAttribute('name')
           ? this.getAttribute('name')
           : '';
  }

  set name(name) {
    this.setAttribute('name', name);
  }

  get readOnly() {
    return this.hasAttribute('readonly')
           ? true
           : false;
  }

  set readOnly(readOnly) {
    if (readOnly) {
      this.setAttribute('readonly', '');
    } else {
      this.removeAttribute('readonly');
    }
  }

  get defaultValue() {
    return this.hasAttribute('value')
           ? this.getAttribute('value')
           : '0';
  }

  set defaultValue(value) {
    if (value == null) {
      this.removeAttribute('value');
    } else {
      this.setAttribute('value', value);
    }
  }

  get valueTextTarget() {
    let id = this.hasAttribute('valuetexttarget')
             ? this.getAttribute('valuetexttarget')
             : '';

    if (!id) {
      return null;
    }

    return this.ownerDocument.getElementById(id);
  }
}

RopiSliderElement._template = html`
<style>
  :host {
    display: block;
    cursor: pointer;
  }

  :host([disabled]) {
    cursor: default;
  }

  :host(:focus) {
    outline: none;
  }

  #handle-focus {
    opacity: 0;
    transition: opacity var(--ropi-animation-duration, 301ms) ease;
    background-color: var(--ropi-slider-handle-color, var(--ropi-color-interactive, blue));
    position: absolute;
    width: 100%;
    height: 100%;
  }

  #handle-focus.visible {
    transition-duration: 1ms;
    opacity: 0.2;
  }

  #handle-focus.hidden {
    opacity: 0 !important;
    transition: opacity var(--ropi-animation-duration, 301ms) ease !important;
  }

  #container {
    height: 2.5rem;
    line-height: 2.5rem;
    vertical-align: middle;
    position: relative;
    margin: 0 0.5rem;
  }

  :host([disabled]) #container {
    opacity: 0.5;
  }

  #rail-area {
    position: absolute;
    height: 100%;
    width: 100%;
    top: 0;
  }

  #rail,
  #slide,
  #steps {
    position: absolute;
    height: 0.125rem;
    margin-top: -0.0625rem;
    width: 100%;
    left: 0;
    top: 50%;
    background-color: var(--ropi-slider-rail-color, var(--ropi-color-base-contrast-medium, grey));
  }

  #steps {
    pointer-events: none;
    z-index: 1;
  }

  #slide {
    background-color: var(--ropi-slider-slide-color, var(--ropi-color-interactive, blue));
    transform: scaleX(0.0001);
    transform-origin: 0 0;
    z-index: 2;
    pointer-events: none;
  }

  #handle-area {
    position: absolute;
    left: 0;
    top: 50%;
    width: 2.5rem;
    height: 2.5rem;
    margin-left: -1.25rem;
    margin-top: -1.25rem;
    border-radius: 100%;
    overflow: hidden;
    z-index: 3;
    transform: translateX(0);
    pointer-events: none;
  }

  #handle {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 1rem;
    height: 1rem;
    margin-top: -0.5rem;
    margin-left: -0.5rem;
    border-radius: 100%;
    display: block;
    background-color: var(--ropi-slider-handle-color, var(--ropi-color-interactive, blue));
  }

  .step {
    position: absolute;
    top: 0;
    width: 0.125rem;
    height: 100%;
    background-color: var(--ropi-slider-step-color, var(--ropi-color-base-contrast, lightgrey));
  }
</style>
<div id="container">
  <div id="steps"></div>
  <div id="rail-area">
    <div id="rail"></div>
  </div>
  <div id="slide"></div>
  <div id="handle-area">
    <div id="handle-focus"></div>
    <div id="handle"></div>
  </div>
</div>`;

customElements.define('ropi-slider', RopiSliderElement);

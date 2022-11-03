import GestureEvents from '../gesture-events/gesture-events.js?v=1637255330';
import TypeUtil from '../type-util/type-util.js?v=1637255330';
import DOMUtil from '../dom-util/dom-util.js?v=1637255330';
import SelectionUtil from '../selection-util/selection-util.js?v=1637255330';

export default class Editable {

  constructor(element) {
    if (element && element.ropiEditable) {
      element.ropiEditable.destroy();
    }

    this.element = element;

    this._clickHandler = (event) => {
      if (event.currentTarget.contentEditable !== 'true') {
          event.preventDefault();
      }
    };

    this._doubleclickHandler = (event) => {
      event.stopPropagation();
      this._activate();
    };

    this._blurHandler = () => {
      if (this.preventCloseOnBlur) {
        return;
      }

      this.element.contentEditable = 'false';

      this.element.removeEventListener('blur', this._blurHandler);

      this.element.dispatchEvent(new CustomEvent('editend', {
        detail: {
          changed: this.element._ropiEditableBefore !== this.element.innerHTML
        },
        bubbles: true
      }));

      this.element.ownerDocument.defaultView.getSelection().removeAllRanges();

      delete this.element._ropiEditableBefore;
    };

    this._keydownHandler = (event) => {
      if (this.element.isContentEditable) {
        if (event.keyCode === 27) {
          // ESC
          this._cancel();
          return;
        }
      } else {
        if (event.keyCode === 32) {
          // Space
          this._activate();
        }
      }
    };

    this._pasteHandler = (event) => {
      event.preventDefault();

      let text = (event.clipboardData || window.clipboardData).getData('text');

      if (this.element.ownerDocument.queryCommandSupported('insertText')) {
        this.element.ownerDocument.execCommand('insertText', false, text);
      } else {
        this.element.ownerDocument.execCommand('paste', false, text);
      }
    };
  }

  _activate() {
    if (this.element.isContentEditable) {
      return;
    }

    this.element._ropiEditableBefore = this.element.innerHTML;
    this.element.contentEditable = 'true';
    this.element.ownerDocument.execCommand('defaultParagraphSeparator', false, 'div');

    this.element.dispatchEvent(new CustomEvent('editstart', {
      bubbles: true
    }));

    let restoreSelection = SelectionUtil.saveSelection(this.element.ownerDocument.defaultView);

    requestAnimationFrame(() => {
        this.element.blur();
        this.element.focus();

        restoreSelection();

        this.element.addEventListener('blur', this._blurHandler);
    });
  }

  _cancel() {
    if (!this.element || this.element._ropiEditableBefore == null) {
      return;
    }

    this.element.innerHTML = this.element._ropiEditableBefore;
    this.element.blur();
  }

  discardChanges() {
    this._cancel();
  }

  set element(element) {
    this.disable();

    this._element = TypeUtil.isObject(element) && element.parentNode !== undefined
                    ? element
                    : null;

    if (this._element) {
      this._element.ropiEditable = this;
    }
  }

  get element() {
    return this._element;
  }

  set preventCloseOnBlur(preventCloseOnBlur) {
    this._preventCloseOnBlur = preventCloseOnBlur ? true : false;
  }

  get preventCloseOnBlur() {
    return this._preventCloseOnBlur ? true : false;
  }

  enable() {
    if (!this.element) {
      return;
    }

    this.disable();

    GestureEvents.enableDoubleClick(this.element);

    //this.element.setAttribute('tabindex', '0');
    this.element.style.userSelect = 'text';
    this.element.style.MozUserSelect = 'text';

    this.element.addEventListener('click', this._clickHandler, true);
    this.element.addEventListener('doubleclick', this._doubleclickHandler);
    this.element.addEventListener('keydown', this._keydownHandler);
    this.element.addEventListener('paste', this._pasteHandler);
  }

  disable() {
    if (!this.element) {
      return;
    }

    this.element.contentEditable = 'false';

  //  this.element.removeAttribute('tabindex');
    this.element.style.userSelect = '';
    this.element.style.MozUserSelect = '';

    this.element.removeEventListener('click', this._clickHandler, true);
    this.element.removeEventListener('doubleclick', this._doubleclickHandler);
    this.element.removeEventListener('keydown', this._keydownHandler);
    this.element.removeEventListener('paste', this._pasteHandler);
  }

  destroy() {
    if (!this.element) {
      return;
    }

    this.disable();
    delete this.element.ropiEditable;
  }
}

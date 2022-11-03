import GestureEvents from '../gesture-events/gesture-events.js?v=1637255330';
import TypeUtil from '../type-util/type-util.js?v=1637255330';

export default class Draggable {

  constructor(element) {
    if (element && element.ropiDraggable) {
      element.ropiDraggable.destroy();
    }

    this.element = element;
    this._registeredTargetDocuments = new Map();

    this._pressHandler = (event) => {
      this._moved = false;
      this._touchmode = false;
      this._longpress = false;
      this._dragoverElements = new Map();

      event.stopPropagation();

      this._ghostElement = this.element.cloneNode(true);

      if (event.type.indexOf('touch') === 0) {
        this._touchmode = true;
      } else {
        this._startX = event.x;
        this._startY = event.y;
      }

      element.addEventListener('longpress', this._longpressHandler);

      this.ghostTargetDocument.addEventListener('mouseup', this._releaseHandler, {passive: false, capture: true});
      this.ghostTargetDocument.addEventListener('touchend', this._releaseHandler, {passive: false, capture: true});
      this.ghostTargetDocument.addEventListener('mousemove', this._activeMoveHandler, {passive: false});
      this.ghostTargetDocument.addEventListener('touchmove', this._activeMoveHandler, {passive: false});
      this.ghostTargetDocument.addEventListener('mousemove', this._moveHandler, {passive: true});
      this.ghostTargetDocument.addEventListener('touchmove', this._moveHandler, {passive: true});
    };

    this._longpressHandler = (event) => {
      if (this._moved) {
        return;
      }

      this._longpress = true;

      if (navigator.vibrate) {
        navigator.vibrate(50);
      }

      this._activeMoveHandler(event.detail.originalEvent);
      this._moveHandler(event.detail.originalEvent, true);
    };

    this._activeMoveHandler = (event) => {
      if (!this._ghostElement) {
        return;
      }

      if (this._touchmode) {
        if (!this._longpress) {
          return;
        }
      } else if (!this._longpress) {
        let deltaX = Math.abs(event.x - this._startX);
        let deltaY = Math.abs(event.y - this._startY);

        if (deltaX < 8 && deltaY < 8) {
          return;
        }
      }

      if (event.cancelable) {
        event.preventDefault();
      }

      if (!this._moved) {
        let x = event.type.indexOf('mouse') === 0 ? event.x : event.touches[0].clientX;
        let y = event.type.indexOf('mouse') === 0 ? event.y : event.touches[0].clientY;

        this._ghostElement.style.left = 0;
        this._ghostElement.style.top = 0;
        this._ghostElement.style.transform = `translate(${x}px, ${y}px)`;
        this._ghostElement.style.zIndex = 40;

        let dragstartEvent = new CustomEvent('dragstart', {
          detail: {
            ghostElement: this._ghostElement
          },
          cancelable: true
        });

        this.element.dispatchEvent(dragstartEvent);

        if (dragstartEvent.defaultPrevented) {
          this._releaseHandler();
          return;
        }

        if (dragstartEvent.detail.ghostElement !== this._ghostElement) {
          this._ghostElement = dragstartEvent.detail.ghostElement;
        }

        this._ghostElement.style.position = 'absolute';
        this._ghostElement.style.pointerEvents = 'none';

        this._userSelect = this.element.style.userSelect;
        this._MozUserSelect = this.element.style.MozUserSelect;
        this.element.style.userSelect = 'none';
        this.element.style.MozUserSelect = 'none';

        this.ghostTargetElement.appendChild(this._ghostElement);

        this._moved = true;

        this._element.ownerDocument.defaultView.getSelection().removeAllRanges();
      }
    };

    this._moveHandler = (event, init) => {
      if (!this._moved) {
        return;
      }

      let pageX = event.type.indexOf('mouse') === 0 ? event.pageX : event.touches[0].pageX;
      let pageY = event.type.indexOf('mouse') === 0 ? event.pageY : event.touches[0].pageY;
      let screenX = event.type.indexOf('mouse') === 0 ? event.screenX : event.touches[0].screenX;
      let screenY = event.type.indexOf('mouse') === 0 ? event.screenY : event.touches[0].screenY;
      let x = event.type.indexOf('mouse') === 0 ? event.x : event.touches[0].clientX;
      let y = event.type.indexOf('mouse') === 0 ? event.y : event.touches[0].clientY;

      if (this._ghostElement) {
        this._ghostElement.style.transform = `translate(${x}px, ${y}px)`;
      }

      if (init) {
        return;
      }

      let dragoverElements = new Map();
      
      for (let targetDocument of this.targetDocuments) {
        let offset = this.getTargetDocumentOffset(targetDocument);
        let dragoverElement = targetDocument.elementFromPoint(
          x + offset.x,
          y + offset.y
        );

        if (dragoverElement) {
          dragoverElements.set(targetDocument, dragoverElement);
        }
      }

      this.element.dispatchEvent(new CustomEvent('drag', {
        detail: {
          ghostElement: this._ghostElement,
          clientX: x,
          clientY: y,
          screenX: screenX,
          screenY: screenY,
          pageX: pageX,
          pageY: pageY
        }
      }));

      for (let targetDocument of dragoverElements.keys()) {
        let offset = this.getTargetDocumentOffset(targetDocument);
        let dragoverElement = dragoverElements.get(targetDocument);
        let dragoverElementBefore = this._dragoverElements.get(targetDocument);

        if (dragoverElementBefore !== dragoverElement) {
          if (dragoverElementBefore) {
            this.element.dispatchEvent(new CustomEvent('dragleave', {
              detail: {
                ghostElement: this._ghostElement,
                element: dragoverElementBefore,
                clientX: x,
                clientY: y,
                screenX: screenX,
                screenY: screenY,
                pageX: pageX,
                pageY: pageY,
                offsetX: offset.x,
                offsetY: offset.y
              }
            }));
          }

          if (dragoverElement) {
            this.element.dispatchEvent(new CustomEvent('dragenter', {
              detail: {
                ghostElement: this._ghostElement,
                element: dragoverElement,
                clientX: x,
                clientY: y,
                screenX: screenX,
                screenY: screenY,
                pageX: pageX,
                pageY: pageY,
                offsetX: offset.x,
                offsetY: offset.y
              }
            }));
          }
        }

        if (dragoverElement) {
          this.element.dispatchEvent(new CustomEvent('dragover', {
            detail: {
              ghostElement: this._ghostElement,
              element: dragoverElement,
              clientX: x,
              clientY: y,
              screenX: screenX,
              screenY: screenY,
              pageX: pageX,
              pageY: pageY,
              offsetX: offset.x,
              offsetY: offset.y
            }
          }));
        }
      }

      this._dragoverElements = dragoverElements;
    };

    this._releaseHandler = (event) => {
      if (this._ghostElement && this._ghostElement.parentNode) {
        this._ghostElement.parentNode.removeChild(this._ghostElement);
      }

      if (this._moved) {
        if (event) {
          event.preventDefault();
          event.stopPropagation();
        }

        for (let dragoverElement of this._dragoverElements.values()) {
          this.element.dispatchEvent(new CustomEvent('drop', {
            detail: {
              element: dragoverElement
            }
          }));
        }

        this.element.dispatchEvent(new CustomEvent('dragend', {
          detail: {
            ghostElement: this._ghostElement
          }
        }));
      }

      this.ghostTargetDocument.removeEventListener('mouseup', this._releaseHandler, {passive: false, capture: true});
      this.ghostTargetDocument.removeEventListener('touchend', this._releaseHandler, {passive: false, capture: true});
      this.ghostTargetDocument.removeEventListener('mousemove', this._activeMoveHandler, {passive: false});
      this.ghostTargetDocument.removeEventListener('touchmove', this._activeMoveHandler, {passive: false});
      this.ghostTargetDocument.removeEventListener('mousemove', this._moveHandler, {passive: true});
      this.ghostTargetDocument.removeEventListener('touchmove', this._moveHandler, {passive: true});
      element.removeEventListener('longpress', this._longpressHandler);

      this.element.style.userSelect = this._userSelect;
      this.element.style.MozUserSelect = this._MozUserSelect;

      delete this._userSelect;
      delete this._MozUserSelect;
      delete this._ghostElement;
      delete this._moved;
      this._dragoverElements = new Map();
    };
  }

  registerTargetDocument(targetDocument, offsetX, offsetY) {
    if (!TypeUtil.isFunction(targetDocument.elementFromPoint)) {
      return;
    }

    this._registeredTargetDocuments.set(targetDocument, {
      offset: {
        x: TypeUtil.isNumber(offsetX) ? offsetX : 0,
        y: TypeUtil.isNumber(offsetY) ? offsetY : 0
      }
    });
  }

  resetTargetDocuments() {
    this._registeredTargetDocuments = new Map();
  }

  getTargetDocumentOffset(targetDocument) {
    if (this._registeredTargetDocuments.has(targetDocument)) {
      return this._registeredTargetDocuments.get(targetDocument).offset;
    }

    return {x: 0, y: 0};
  }

  get targetDocuments() {
    if (this._registeredTargetDocuments.size > 0) {
      let targetDocuments = [];

      for (let targetDocument of this._registeredTargetDocuments.keys()) {
        targetDocuments.push(targetDocument);
      }

      return targetDocuments;
    }

    return [this.element.ownerDocument];
  }

  set ghostTargetDocument(ghostTargetDocument) {
    if (TypeUtil.isFunction(ghostTargetDocument.elementFromPoint)) {
      this._ghostTargetDocument = ghostTargetDocument;
    }
  }

  get ghostTargetDocument() {
    return this._ghostTargetDocument || this.element.ownerDocument;
  }

  get ghostTargetElement() {
    if (this.ghostTargetDocument.body !== undefined) {
      return this.ghostTargetDocument.body;
    }

    return this.ghostTargetDocument;
  }

  set element(element) {
    this.disable();

    this._element = TypeUtil.isObject(element) && element.parentNode !== undefined ? element : null;
    if (this._element) {
      this._element.ropiDraggable = this;
    }
  }

  get element() {
    return this._element;
  }

  enable() {
    if (!this.element) {
      return;
    }

    this.disable();

    this.element.addEventListener('mousedown', this._pressHandler, {
      passive: true
    });

    this.element.addEventListener('touchstart', this._pressHandler, {
      passive: true
    });

    GestureEvents.enableLongPress(this.element);
  }

  disable() {
    if (!this.element) {
      return;
    }

    this.element.removeEventListener('mousedown', this._pressHandler, {
      passive: true
    });

    this.element.removeEventListener('touchstart', this._pressHandler, {
      passive: true
    });
  }

  destroy() {
    if (!this.element) {
      return;
    }

    this.disable();
    delete this.element.ropiDraggable;
  }
}

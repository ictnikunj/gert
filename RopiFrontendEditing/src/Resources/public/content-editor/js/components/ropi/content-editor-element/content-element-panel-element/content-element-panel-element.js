import RopiHTMLElement from '../../html-element/html-element.js?v=1637255330';
import html from '../../html-tag/html-tag.js?v=1637255330';

import DOMUtil from '../../dom-util/dom-util.js?v=1637255330';
import Logger from '../../logger/logger.js?v=1637255330';
import TranslateElement from '../../translate-element/translate-element.js?v=1637255330';

import '../../for-element/for-element.js?v=1637255330';
import '../../material-icon-element/material-icon-element.js?v=1637255330';
import '../../touchable-element/touchable-element.js?v=1637255330';
import '../../draggable-element/draggable-element.js?v=1637255330';
import '../../subheader-element/subheader-element.js?v=1637255330';
import '../../tabs-element/tabs-element.js?v=1637255330';
import '../../menu-element/menu-element.js?v=1637255330';
import DialogElement from '../../dialog-element/dialog-element.js?v=1637255330';

export default class RopiContentElementPanelElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['canvas', 'open'];
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (name === 'canvas') {
      if (this._canvas) {
        this._canvas.removeEventListener('load', this._canvasLoadHandler);
        this._canvas.removeEventListener('change', this._canvasChangeHandler);
      }

      this._canvas = value
                     ? this.getRootNode().getElementById(value)
                     : null;

      if (this._canvas) {
        this._canvas.addEventListener('load', this._canvasLoadHandler);
        this._canvas.addEventListener('change', this._canvasChangeHandler);
      }
    } else if (name === 'open') {
      this.closeMenus();
    }
  }

  constructor() {
    super();

    this._canvas = null;
    this._close = this.shadowRoot.getElementById('close');
    this._contentElements = [];
    this._contentPresets = [];
    this._flap = this.shadowRoot.getElementById('flap');
    this._deleteDialog = this.shadowRoot.getElementById('delete-dialog');

    this._snippetsChangeHandler = () => {
      this._close.setAttribute('title', TranslateElement.translate('Close'));
    };

    this._flapClickHandler = () => {
      if (this.hasAttribute('open')) {
        this.removeAttribute('open');
      } else {
        this.setAttribute('open', '');
      }

      this.dispatchEvent(new CustomEvent('open'));
    };

    this._canvasLoadHandler = () => {
      this._initRenderedContentElements();
      this.update();
    };

    this._canvasChangeHandler = () => {
      this._initRenderedContentElements();
      this.update();
    };

    this._dragstartHandler = (event) => {
      if (!(event instanceof CustomEvent)) {
        event.preventDefault();
        return;
      }

      this.dispatchEvent(new CustomEvent('contentelementdragstart'));

      this._dragColor = event.target.getAttribute('data-content-element-color');

      let canvasRect = this._canvas.getBoundingClientRect();

      event.currentTarget.ropiDraggable.registerTargetDocument(this._canvas.contentEditor.shadowRoot);
      event.currentTarget.ropiDraggable.registerTargetDocument(
        this._canvas.workingDocument,
        0 - canvasRect.left,
        0 - canvasRect.top
      );

      let editorRect = this.parentNode.host.getBoundingClientRect();
      event.detail.ghostElement.style.left = (0 - editorRect.left) + 'px';
      event.detail.ghostElement.style.top = (0 - editorRect.top) + 'px';

      this._canvas.setAttribute(
        'dragging',
        event.target.getAttribute('data-content-element-item')
      );
    };

    this._canvasDragstartHandler = (event) => {
      if (!(event instanceof CustomEvent)) {
        event.preventDefault();
        return;
      }

      let renderedContentElement = event.currentTarget;
      if (renderedContentElement.isContentEditable) {
        event.stopPropagation();
        event.preventDefault();
        return;
      }

      let editingElement = renderedContentElement.querySelector('[data-ropi-content-editable][contentEditable="true"]');
      if (editingElement) {
        event.stopPropagation();
        event.preventDefault();
        return;
      }

      this.dispatchEvent(new CustomEvent('contentelementdragstart'));

      let type = event.currentTarget.getAttribute('data-ropi-content-element');
      let draggableItem = this.shadowRoot.querySelector(
        `[data-content-element-item="${type}"]`
      );

      if (!draggableItem) {
        event.stopPropagation();
        event.preventDefault();
        return;
      }

      this._dragColor = draggableItem.getAttribute('data-content-element-color');

      renderedContentElement.classList.add('ropi-content-element-moving');

      let canvasRect = this._canvas.getBoundingClientRect();

      // Define target document and ghost target document first on start
      // dragging, because browser does not fire mouse events otherwise
      // because of the iframe
      renderedContentElement.ropiDraggable.ghostTargetDocument = this.getRootNode();
      renderedContentElement.ropiDraggable.registerTargetDocument(this._canvas.workingDocument);
      renderedContentElement.ropiDraggable.registerTargetDocument(
        this._canvas.contentEditor.shadowRoot,
        canvasRect.left,
        canvasRect.top
      );

      event.detail.ghostElement = draggableItem.cloneNode(true);
      event.detail.ghostElement.style.float = 'left';
      event.detail.ghostElement.style.top = canvasRect.top + 'px';
      event.detail.ghostElement.style.left = canvasRect.left + 'px';
      event.detail.ghostElement.style.zIndex = 100;

      this._canvas.setAttribute('dragging', type);
    };

    this._dragenterHandler = (event) => {
      if (event.detail.element.ownerDocument === this._canvas.workingDocument) {
        let contentAreaElement = event.detail.element.closest('[data-ropi-content-area]');
        if (contentAreaElement && !contentAreaElement.classList.contains('ropi-content-area-disallowed')) {
          contentAreaElement.classList.add('ropi-content-area-dragover');
          this._contentAreaDragover = contentAreaElement;
        }

        this._removeDropMarker();
      } else {
        if (event.detail.element.id === 'drop-area-delete') {
          event.detail.element.classList.add('dragover');
        }
      }
    };

    this._dragleaveHandler = (event) => {
      if (event.detail.element.ownerDocument === this._canvas.workingDocument) {
        let contentAreaElement = event.detail.element.closest('[data-ropi-content-area]');
        if (contentAreaElement && contentAreaElement.classList.contains('ropi-content-area-dragover')) {
          contentAreaElement.classList.remove('ropi-content-area-dragover');
          delete this._contentAreaDragover;
        }

        this._removeDropMarker();
      } else {
        if (event.detail.element.id === 'drop-area-delete') {
          event.detail.element.classList.remove('dragover');
        }
      }
    };

    this._dragoverHandler = (event) => {
      if (event.detail.element.ownerDocument !== this._canvas.workingDocument) {
        return;
      }

      if (!this._contentAreaDragover) {
        return;
      }

      this._removeDropMarker();

      if (this._contentAreaDragover.children.length === 0) {
        this._createDropMarker();
        this._contentAreaDragover.appendChild(this._dropMarker);
      } else {
        let y = event.detail.clientY + event.detail.offsetY;
        let closestElement;
        let closestElementRect;

        for (let contentElement of this._contentAreaDragover.children) {
          let elementRect = contentElement.getBoundingClientRect();

          if (!closestElement) {
            closestElement = contentElement;
            closestElementRect = elementRect;
            continue;
          }

          if (elementRect.y < y) {
            closestElement = contentElement;
            closestElementRect = elementRect;
            continue;
          }

          break;
        }

        if (closestElement) {
          let closestElementCenterY = closestElementRect.y + closestElementRect.height * 0.5;
          if (y < closestElementCenterY) {
            this._createDropMarker();
            closestElement.parentNode.insertBefore(this._dropMarker, closestElement);
          } else {
            this._createDropMarker();
            DOMUtil.insertAfter(closestElement, this._dropMarker);
          }
        } else {
          this._createDropMarker();

          this._contentAreaDragover.appendChild(this._dropMarker);
        }
      }
    };

    this._dropHandler = (event) => {
      let isNew = !event.currentTarget.hasAttribute('data-ropi-content-element');

      if (event.detail.element.ownerDocument === this._canvas.workingDocument) {
        let contentElement = isNew
                             ? this.getContentElementByType(event.currentTarget.getAttribute('data-content-element-item')).cloneNode(true)
                             : event.currentTarget.ropiContentElement;

        if (!this._dropMarker || !this._dropMarker.parentNode || !contentElement || !this._contentAreaDragover) {
          return;
        }

        contentElement.canvas = this._canvas;

        if (event.currentTarget.hasAttribute('data-content-preset')) {
          contentElement.renderElement(event.currentTarget.getAttribute('data-content-preset-structure'));
        }

        this._dropMarker.parentNode.replaceChild(
          contentElement.renderedElement,
          this._dropMarker
        );
      } else {
        if (event.detail.element.id === 'drop-area-delete' && event.currentTarget.parentNode) {
          event.detail.element.classList.remove('dragover');

          if (isNew) {
            return;
          }

          event.currentTarget.parentNode.removeChild(event.currentTarget);
        } else {
          return;
        }
      }

      this._canvas.parseContentElements();
      this.update();

      this._canvas.contentEditor.dispatchEvent(new CustomEvent('dropaction', {
        detail: {
          isNew: isNew
        }
      }));

      this._canvas.contentEditor.dispatchEvent(new CustomEvent('contentdatachange', {
        bubbles: false
      }));
    };

    this._dragendHandler = (event) => {
      this.dispatchEvent(new CustomEvent('contentelementdragend'));

      event.currentTarget.classList.remove('ropi-content-element-moving');
      event.currentTarget.classList.remove('ropi-content-element-mouseover');

      event.currentTarget.style.userSelect = event.currentTarget._ropiUserSelect;

      event.currentTarget.blur();

      this._removeDropMarker();

      if (this._contentAreaDragover) {
        this._contentAreaDragover.classList.remove('ropi-content-area-dragover');
        delete this._contentAreaDragover;
      }

      requestAnimationFrame(() => {
        // Remove attribute delayed, to prevent selecting element after drop
        this._canvas.removeAttribute('dragging');
      });
    };

    this._closeHandler = () => {
      this.removeAttribute('open');
    };

    this._resizeHandler = () => {
      if (this.hasAttribute('noflap')) {
        // Prevent flap animaton if software keyboard is opening,
        // because it looks weird with animation
        this._flap.style.display = 'none';
        DOMUtil.forceReflow(this._flap);
        this._flap.style.display = '';
      }
    };

    this._clickHandler = () => {
      this.closeMenus();
    };
  }

  _createDropMarker() {
    this._removeDropMarker();
    this._dropMarker = this._canvas.createDropMarker(this._dragColor);
  }

  _removeDropMarker() {
    if (this._dropMarker && this._dropMarker.parentNode) {
      this._dropMarker.parentNode.removeChild(this._dropMarker);
      delete this._dropMarker;
    }
  }

  connectedCallback() {
    this._flap.addEventListener('click', this._flapClickHandler);
    this._close.addEventListener('click', this._closeHandler);
    window.addEventListener('resize', this._resizeHandler);

    this.addEventListener('click', this._clickHandler);

    TranslateElement.bind(this._snippetsChangeHandler);
    this._snippetsChangeHandler();
  }

  disconnectedCallback() {
    this._flap.removeEventListener('click', this._flapClickHandler);
    window.removeEventListener('resize', this._resizeHandler);

    this.removeEventListener('click', this._clickHandler);

    TranslateElement.unbind(this._snippetsChangeHandler);
  }

  update() {
    if (!this._canvas) {
      return;
    }

    this._updateAllowedElements();
    this._updateCanvasDragEvents();
  }

  set contentElements(contentElements) {
    this._contentElements = contentElements;

    let groupedContentElements = [];

    for (let contentElement of contentElements) {
      if (!groupedContentElements[contentElement.group]) {
        groupedContentElements[contentElement.group] = {
          name: contentElement.group,
          elements: []
        };
      }

      groupedContentElements[contentElement.group].elements.push(
        contentElement
      );
    }

    let forElement = this.shadowRoot.querySelector('ropi-for[as="contentElementGroup"]');
    forElement.each = groupedContentElements;
  }

  get contentElements() {
    return this._contentElements;
  }

  set contentPresets(contentPresets) {
    this._contentPresets = [];

    for (let contentPreset of contentPresets) {
      this._contentPresets.push(contentPreset);
    }

    this._contentPresets.sort((a, b) => {
      if(a.name < b.name) { return -1; }
      if(a.name > b.name) { return 1; }
      return 0;
    });

    let forElement = this.shadowRoot.querySelector('ropi-for[as="contentPreset"]');

    forElement.oniterate = (event) => {
      let presetElement = event.detail.elements[0];
      let draggablePresetElement = presetElement.querySelector('[data-content-preset]');
      let deleteButton = presetElement.querySelector('.delete-preset-button');
      let moreButton = presetElement.querySelector('.list-item-more');
      moreButton.setAttribute('title', TranslateElement.translate('presetActions'));
      let menu = presetElement.querySelector('ropi-menu');
      let name = draggablePresetElement.getAttribute('data-content-preset-name');

      if (event.detail.value.readonly) {
        moreButton.setAttribute('disabled', '');
      } else {
        moreButton.addEventListener('click', (event) => {
          if (menu.hasAttribute('open')) {
            this.closeMenus();
          } else {
            this.closeMenus();
            menu.toggleAttribute('open');
          }

          event.stopPropagation();
        });

        deleteButton.addEventListener('click', () => {
          this._deleteDialog.querySelector('.dialog-text ropi-translate').setAttribute('vars', JSON.stringify({
            preset: {
              name: name
            }
          }));

          this._deleteDialog.setAttribute('open', '');

          this._deleteDialog.ondialogclose = (event) => {
            if (event.detail.action === DialogElement.ACTION_PRIMARY) {
              let presetDeleteEvent = new CustomEvent('presetdelete', {
                detail: {
                  name: name
                },
                composed: true,
                cancelable: true
              });

              this.dispatchEvent(presetDeleteEvent);

              if (!presetDeleteEvent.defaultPrevented) {
                let nameEscaped = name.replace(/"/g, '\\"');
                let existingPreset = this._canvas.contentEditor.querySelector('ropi-content-preset[name="' + nameEscaped + '"]:not([readonly])');
                if (existingPreset && existingPreset.parentNode) {
                  existingPreset.parentNode.removeChild(existingPreset);

                  this.contentPresets = this._canvas.contentEditor.contentPresets;
                  this.update();
                }
              }
            }
          };
        });
      }
    };

    forElement.each = this._contentPresets;
  }

  get contentPresets() {
    return this._contentPresets;
  }

  getContentElementByType(type) {
    for (let contentElement of this.contentElements) {
      if (contentElement.type === type) {
        return contentElement;
      }
    }

    return null;
  }

  closeMenus() {
    let openMenus = this.shadowRoot.querySelectorAll('ropi-menu[open]');
    for (let menu of openMenus) {
      menu.removeAttribute('open');
    }
  }

  _updateAllowedElements() {
    let contentAreaElements = this._canvas.contentAreaElements;
    let contentElements = this.shadowRoot.querySelectorAll('[data-content-element-item]');
    let numAllowedElements = 0;

    for (let contentElement of contentElements) {
      let contentElementAllowed = false;
      let contentElementType = contentElement.getAttribute('data-content-element-item');

      for (let contentAreaElement of contentAreaElements) {
        if (this._canvas.isContentElementTypeAllowedForArea(contentElementType, contentAreaElement)) {
          contentElementAllowed = true;
          break;
        }
      }

      contentElement.setAttribute('disabled', '');
      contentElement.removeEventListener('dragstart', this._dragstartHandler, {passive: true});
      contentElement.removeEventListener('dragenter', this._dragenterHandler, {passive: true});
      //contentElement.removeEventListener('drag', this._dragHandler, {passive: true});
      contentElement.removeEventListener('dragleave', this._dragleaveHandler, {passive: true});
      contentElement.removeEventListener('dragover', this._dragoverHandler, {passive: true});
      contentElement.removeEventListener('drop', this._dropHandler, {passive: true});
      contentElement.removeEventListener('dragend', this._dragendHandler, {passive: true});

      if (contentElementAllowed && !this._canvas.hasAttribute('loading')) {
        contentElement.draggable.ghostTargetDocument = this.getRootNode();

        contentElement.removeAttribute('disabled');
        contentElement.addEventListener('dragstart', this._dragstartHandler, {passive: true});
        contentElement.addEventListener('dragenter', this._dragenterHandler, {passive: true});
        //contentElement.addEventListener('drag', this._dragHandler, {passive: true});
        contentElement.addEventListener('dragleave', this._dragleaveHandler, {passive: true});
        contentElement.addEventListener('dragover', this._dragoverHandler, {passive: true});
        contentElement.addEventListener('drop', this._dropHandler, {passive: true});
        contentElement.addEventListener('dragend', this._dragendHandler, {passive: true});

        numAllowedElements++;
      }
    }

    /*
    if (numAllowedElements > 0) {
        this.removeAttribute('noflap');
    } else {
        this.setAttribute('noflap', '');
    }
    */
  }

  _updateCanvasDragEvents() {
    this._canvas.contentElements.forEach((contentElement) => {
      contentElement.removeEventListener('dragstart', this._canvasDragstartHandler);
      contentElement.removeEventListener('dragenter', this._dragenterHandler, {passive: true});
      contentElement.removeEventListener('dragleave', this._dragleaveHandler, {passive: true});
      contentElement.removeEventListener('dragover', this._dragoverHandler, {passive: true});
      contentElement.removeEventListener('drop', this._dropHandler, {passive: true});
      contentElement.removeEventListener('dragend', this._dragendHandler, {passive: true});

      contentElement.addEventListener('dragstart', this._canvasDragstartHandler);
      contentElement.addEventListener('dragenter', this._dragenterHandler, {passive: true});
      contentElement.addEventListener('dragleave', this._dragleaveHandler, {passive: true});
      contentElement.addEventListener('dragover', this._dragoverHandler, {passive: true});
      contentElement.addEventListener('drop', this._dropHandler, {passive: true});
      contentElement.addEventListener('dragend', this._dragendHandler, {passive: true});
    });
  }

  _initRenderedContentElements() {
    let renderedContentElements = this._canvas.workingDocument.querySelectorAll(
      '[data-ropi-content-element]'
    );

    renderedContentElements.forEach((renderedElement) => {
      if (renderedElement.ropiContentElement) {
        return;
      }

      let type = renderedElement.getAttribute('data-ropi-content-element');
      if (!type) {
        Logger.logWarning(
          'Following rendered content element has no type:',
          renderedElement
        );
        return;
      }

      let contentElement = this.getContentElementByType(type);
      if (!contentElement) {
        Logger.logDebug(
          'Following rendered content element has no appropriate content element:',
          renderedElement
        );
        return;
      }

      contentElement = contentElement.cloneNode(true);
      contentElement.canvas = this._canvas;
      contentElement.initializeFromRenderedElement(renderedElement);
    });
  }
}

RopiContentElementPanelElement._template = html`
<style>
:host {
  display: block;
  transition: transform var(--ropi-animation-duration, 301ms) ease;
  transform: translateX(-100%);
  width: calc(100% - 4rem);
  max-width: 20rem;
  background-color: var(--ropi-color-base, black);
  z-index: 30;
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  border-right: solid 0.0625rem var(--ropi-color-contrast-medium, grey);
}

#flap {
  width: 3rem;
  height: auto;
  padding: 1rem 0;
  background-color: inherit;
  position: absolute;
  right: -3.0625rem;
  bottom: 0;
  border: solid 0.0625rem var(--ropi-color-contrast-medium, grey);
  border-left: none;
  border-top-right-radius: 1rem;
  border-bottom-right-radius: 1rem;
  text-orientation: sideways;
  writing-mode: vertical-lr;
  line-height: 3rem;
  text-align: center;
  overflow: hidden;
  transform: translateX(0);
  transition: transform var(--ropi-animation-duration, 301ms) ease;
  transition-delay: var(--ropi-animation-duration, 301ms);
}

:host([noflap]) #flap {
  transition-delay: 0ms;
  pointer-events: none;
  transform: translateX(-100%);
}

:host([open]) {
  transform: translateX(0);
}

.panel-content {
  overflow-y: auto;
  pointer-events: none;
  position: absolute;
  top: 3rem;
  bottom: 0;
  width: 100%;
}

:host([open]) > .panel-content {
  pointer-events: auto;
}

ropi-subheader {
  border-top: solid 1px var(--ropi-color-material-25);
}

ropi-subheader:first-child {
  border: none;
}

ropi-draggable[disabled],
ropi-touchable[disabled] {
  opacity: var(--ropi-disabled-opacity, 0.33);
}

#actionbar {
  height: 3rem;
  width: 100%;
  position: relative;
}

#actionbar > ropi-touchable {
  width: 3rem;
  height: 3rem;
  line-height: 3rem;
  text-align: center;
  position: absolute;
  top: 0;
}

#close {
  left: 0;
}

[data-element="icon"] {
    color: var(--ropi-color-interactive, blue);
}

.list-item-more {
  position: relative;
  padding: 0;
}

.list-item-more > ropi-material-icon {
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  padding-right: var(--ropi-grid-outer-gutter-height, 0.75rem);
}

ropi-menu {
    width: auto;
}

ropi-menu ropi-touchable {
  padding: var(--ropi-grid-outer-gutter-height, 0.75rem)
           var(--ropi-grid-outer-gutter-width, 1rem);
}

</style>
<ropi-touchable id="flap">
  <ropi-translate>contentElements</ropi-translate>
</ropi-touchable>
<div id="actionbar">
  <ropi-touchable id="close">
    <ropi-material-icon>arrow_back</ropi-material-icon>
  </ropi-touchable>
</div>
<div class="panel-content">
  <ropi-tabs>
      <ropi-touchable slot="tab">
        <ropi-translate>basicElements</ropi-translate>
      </ropi-touchable>
      <ropi-touchable slot="tab">
        <ropi-translate>presets</ropi-translate>
      </ropi-touchable>
      <div slot="tabpanel">
        <ropi-for as="contentElementGroup">
          <template>
            <ropi-subheader data-contentElementGroup="name"></ropi-subheader>
            <ropi-for data-contentElementGroup="elements" as="element">
              <template>
                <ropi-draggable
                  disabled
                  data-content-element-item="{{element.type}}"
                  data-content-element-color="{{element.color}}"
                  style="background-color: var(--ropi-color-base, black); max-width: 320px;">
                  <div
                    style="padding: var(--ropi-grid-outer-gutter-height, 0.75rem)
                           var(--ropi-grid-outer-gutter-width, 1rem)">
                    <ropi-material-icon
                      data-element="icon"
                      style="padding-right: var(--ropi-grid-outer-gutter-height, 0.75rem);
                             color: {{element.color}}">
                    </ropi-material-icon>
                    <span data-element="name"></span>
                  </div>
                </ropi-draggable>
              </template>
            </ropi-for>
          </template>
        </ropi-for>
      </div>
      <div slot="tabpanel">
        <ropi-for as="contentPreset">
          <template>
            <div style="display: grid; grid-template-columns: auto 3rem; position: relative">
              <ropi-draggable
                disabled
                data-content-preset
                data-content-preset-name="{{contentPreset.name}}"
                data-content-element-item="{{contentPreset.type}}"
                data-content-preset-structure="{{contentPreset.structure}}"
                style="background-color: var(--ropi-color-base, black); max-width: 320px;">
                  <div
                    style="padding: var(--ropi-grid-outer-gutter-height, 0.75rem)
                           var(--ropi-grid-outer-gutter-width, 1rem)">
                    <span data-contentPreset="name"></span>
                    <div style="color: var(--ropi-color-font-50, grey); font-size: var(--ropi-font-size-s, 0.75rem)">
                      <span data-contentPreset="formattedTime"></span>
                    </div>
                  </div>
              </ropi-draggable>
              <ropi-touchable class="list-item-more" data-id="{{version.id}}">
                <ropi-material-icon>more_vert</ropi-material-icon>
              </ropi-touchable>
              <ropi-menu type="action-right">
                <ropi-touchable class="delete-preset-button">
                  <ropi-translate>deletePreset</ropi-translate>
                </ropi-touchable>
              </ropi-menu>
            </div>
          </template>
        </ropi-for>
      </div>
  </ropi-tabs>
</div>
<ropi-dialog id="delete-dialog">
  <div slot="title">
    <ropi-translate>deletePreset</ropi-translate>
  </div>
  <div slot="content">
    <div class="dialog-text">
      <ropi-translate>confirmDeletePreset</ropi-translate>
    </div>
  </div>
  <div slot="primary">
    <ropi-translate>Confirm</ropi-translate>
  </div>
  <div slot="cancel">
    <ropi-translate>Cancel</ropi-translate>
  </div>
</ropi-dialog>
`;

customElements.define('ropi-content-element-panel', RopiContentElementPanelElement);

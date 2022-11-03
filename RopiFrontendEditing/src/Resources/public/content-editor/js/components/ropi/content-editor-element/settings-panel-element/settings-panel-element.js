import RopiHTMLElement from '../../html-element/html-element.js?v=1637255330';
import html from '../../html-tag/html-tag.js?v=1637255330';

import DataParser from '../data-parser/data-parser.js?v=1637255330';
import DOMUtil from '../../dom-util/dom-util.js?v=1637255330';
import TranslateElement from '../../translate-element/translate-element.js?v=1637255330';
import DialogElement from '../../dialog-element/dialog-element.js?v=1637255330';

import '../../vertical-scroll-element/vertical-scroll-element.js?v=1637255330';
import '../../breadcrumb-item-element/breadcrumb-item-element.js?v=1637255330';
import '../../material-icon-element/material-icon-element.js?v=1637255330';
import '../../touchable-element/touchable-element.js?v=1637255330';
import '../content-preset-element.js?v=1637255330';

export default class RopiSettingsPanelElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['canvas', 'open'];
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (name === 'canvas') {
      this._canvas = value
                     ? this.getRootNode().getElementById(value)
                     : null;
    } else if (name === 'open') {
      if (!this.hasAttribute('open') && this._canvas) {
        this._canvas.activeContentElement = null;
      }
    }
  }

  constructor() {
    super();

    this._contentElement = null;
    this._canvas = null;
    this._close = this.shadowRoot.getElementById('close');
    this._reload = this.shadowRoot.getElementById('reload');
    this._delete = this.shadowRoot.getElementById('delete');
    this._savePreset = this.shadowRoot.getElementById('savePreset');
    this._duplicate = this.shadowRoot.getElementById('duplicate');
    this._moveToPreviousArea = this.shadowRoot.getElementById('moveToPreviousArea');
    this._moveToNextArea = this.shadowRoot.getElementById('moveToNextArea');
    this._moveToPreviousPosition = this.shadowRoot.getElementById('moveToPreviousPosition');
    this._moveToNextPosition = this.shadowRoot.getElementById('moveToNextPosition');
    this._breadcrumb = this.shadowRoot.getElementById('breadcrumb');
    this._targetSlot = document.createElement('slot');
    this._targetSlot.setAttribute('name', 'ropi-settings-panel-configuration');
    this._deleteDialog = this.shadowRoot.getElementById('delete-dialog');
    this._savePresetDialog = this.shadowRoot.getElementById('savePreset-dialog');
    this._overwritePresetDialog = this.shadowRoot.getElementById('overwritePreset-dialog');

    this._snippetsChangeHandler = () => {
      this._close.setAttribute('title', TranslateElement.translate('Close'));
      this._reload.setAttribute('title', TranslateElement.translate('reloadElement'));
      this._delete.setAttribute('title', TranslateElement.translate('deleteElement'));
      this._savePreset.setAttribute('title', TranslateElement.translate('savePreset'));
      this._duplicate.setAttribute('title', TranslateElement.translate('duplicateElement'));
      this._moveToPreviousArea.setAttribute('title', TranslateElement.translate('moveToPreviousArea'));
      this._moveToNextArea.setAttribute('title', TranslateElement.translate('moveToNextArea'));
      this._moveToPreviousPosition.setAttribute('title', TranslateElement.translate('moveToPreviousPosition'));
      this._moveToNextPosition.setAttribute('title', TranslateElement.translate('moveToNextPosition'));
    };

    this._closeHandler = () => {
      this.removeAttribute('open');
    };

    this._reloadHandler = () => {
      if (this._contentElement) {
        this._contentElement.renderElement();
      }
    };

    this._duplicateHandler = () => {
      this._contentElement.duplicate();
    };

    this._moveToPreviousPositionHandler = () => {
      let previousElementSibling = this._contentElement.renderedElement.previousElementSibling;
      if (!previousElementSibling) {
        return;
      }

      previousElementSibling.parentNode.insertBefore(
        this._contentElement.renderedElement,
        previousElementSibling
      );

      this._contentElement.renderedElement.scrollIntoView({
        behavior: "smooth",
        block: "start"
      });
    };

    this._moveToNextPositionHandler = () => {
      let nextElementSibling = this._contentElement.renderedElement.nextElementSibling;
      if (!nextElementSibling) {
        return;
      }

      DOMUtil.insertAfter(nextElementSibling, this._contentElement.renderedElement);
      this._contentElement.renderedElement.scrollIntoView({
        behavior: "smooth",
        block: "start"
      });
    };

    this._moveToPreviousAreaHandler = () => {
      let previousAreaElement;

      for (let areaElement of this._canvas.contentAreaElements) {
        if (areaElement === this._contentElement.renderedElement.parentNode) {
          break;
        }

        if (areaElement.hasAttribute('disabled') || areaElement.clientHeight === 0) {
          continue;
        }

        let allowed = this._canvas.isContentElementTypeAllowedForArea(
          this._contentElement.type,
          areaElement
        );

        if (allowed && !this._contentElement.renderedElement.contains(areaElement)) {
          previousAreaElement = areaElement;
        }
      }

      if (previousAreaElement) {
        DOMUtil.prependChild(previousAreaElement, this._contentElement.renderedElement);

        let areaChangeEvent = new CustomEvent('areachange', {
          detail: {
            area: previousAreaElement
          },
          bubbles: true,
          cancelable: true
        });

        this._contentElement.renderedElement.dispatchEvent(areaChangeEvent);

        if (!areaChangeEvent.defaultPrevented) {
          this._contentElement.renderedElement.scrollIntoView({
            behavior: "smooth",
            block: "start"
          });
        }
      }
    };

    this._moveToNextAreaHandler = () => {
      let nextAreaElement;

      for (let i = this._canvas.contentAreaElements.length - 1; i >= 0; i--) {
        let areaElement = this._canvas.contentAreaElements[i];

        if (areaElement === this._contentElement.renderedElement.parentNode) {
          break;
        }

        if (areaElement.hasAttribute('disabled') || areaElement.clientHeight === 0) {
          continue;
        }

        let allowed = this._canvas.isContentElementTypeAllowedForArea(
          this._contentElement.type,
          areaElement
        );

        if (allowed && !this._contentElement.renderedElement.contains(areaElement)) {
          nextAreaElement = areaElement;
        }
      }

      if (nextAreaElement) {
        DOMUtil.prependChild(nextAreaElement, this._contentElement.renderedElement);

        let areaChangeEvent = new CustomEvent('areachange', {
          detail: {
            area: nextAreaElement
          },
          bubbles: true,
          cancelable: true
        });

        this._contentElement.renderedElement.dispatchEvent(areaChangeEvent);

        if (!areaChangeEvent.defaultPrevented) {
          this._contentElement.renderedElement.scrollIntoView({
            behavior: "smooth",
            block: "start"
          });
        }
      }
    };

    this._deleteHandler = () => {
      document.body.appendChild(this._deleteDialog);

      this._deleteDialog.querySelector('.dialog-text ropi-translate').setAttribute('vars', JSON.stringify({
        element: {
          name: this._contentElement.name
        }
      }));

      this._deleteDialog.setAttribute('open', '');

      this._deleteDialog.ondialogclose = (event) => {
        if (event.detail.action === DialogElement.ACTION_PRIMARY) {
          this._canvas.activeContentElement = null;

          for (let element of this._targetSlot.assignedElements()) {
            element.removeAttribute('slot');
          }

          this.removeAttribute('open');

          this._contentElement.renderedElement.parentNode.removeChild(
            this._contentElement.renderedElement
          );
        }
      };

      this._deleteDialog.ondialogclosecomplete = () => {
        this.shadowRoot.appendChild(this._deleteDialog);
      }
    };

    this._savePresetHandler = () => {
      if (!this._canvas.activeContentElement) {
        return;
      }

      let renderedElement = this._canvas.activeContentElement.renderedElement;
      if (!renderedElement) {
        return;
      }

      document.body.appendChild(this._savePresetDialog);

      this._savePresetDialog.querySelector('[slot="title"] ropi-translate').setAttribute('vars', JSON.stringify({
        element: {
          name: this._contentElement.name
        }
      }));

      this._savePresetDialog.querySelector('.dialog-text ropi-translate').setAttribute('vars', JSON.stringify({
        element: {
          name: this._contentElement.name
        }
      }));

      let nameInput = this._savePresetDialog.querySelector('ropi-textfield[name="presetName"]');
      nameInput.value = '';

      this._savePresetDialog.setAttribute('open', '');

      nameInput.focus();

      this._savePresetDialog.ondialogclose = (event) => {
        if (event.detail.action !== DialogElement.ACTION_PRIMARY) {
          return;
        }

        let name = nameInput.value.trim();

        if (name === '') {
          event.preventDefault();
          this._savePresetDialog.pushError(TranslateElement.translate('presetNameEmpty'));
          return;
        }

        for (let element of this._targetSlot.assignedElements()) {
          element.removeAttribute('slot');
        }

        this.removeAttribute('open');

        let nameEscaped = name.replace(/"/g, '\\"');
        let existingPreset = this._canvas.contentEditor.querySelector('ropi-content-preset[name="' + nameEscaped + '"]:not([readonly])');
        if (existingPreset) {
          document.body.appendChild(this._overwritePresetDialog);

          this._overwritePresetDialog.querySelector('.dialog-text ropi-translate').setAttribute('vars', JSON.stringify({
            presetName: name
          }));

          this._overwritePresetDialog.setAttribute('open', '');

          this._overwritePresetDialog.ondialogclose = (event) => {
            if (event.detail.action !== DialogElement.ACTION_PRIMARY) {
              return;
            }

            this._dispatchPresetSaveEvent({
              overwrite: true,
              name: name,
              creationTimestamp: Date.now(),
              data: DataParser.parseFromElement(renderedElement, true)
            });
          };
        } else {
          this._dispatchPresetSaveEvent({
            overwrite: false,
            name: name,
            creationTimestamp: Date.now(),
            data: DataParser.parseFromElement(renderedElement, true)
          });
        }
      };

      this._savePresetDialog.ondialogclosecomplete = () => {
        this.shadowRoot.appendChild(this._savePresetDialog);
      }
    };
  }

  _dispatchPresetSaveEvent(detail) {
    let presetSaveEvent = new CustomEvent('presetsave', {
      detail: detail,
      composed: true,
      cancelable: true
    });

    this.dispatchEvent(presetSaveEvent);

    if (!presetSaveEvent.defaultPrevented) {
      let preset = document.createElement('ropi-content-preset');

      preset.setAttribute('name', presetSaveEvent.detail.name);
      preset.setAttribute('time', presetSaveEvent.detail.creationTimestamp);
      preset.setAttribute('type', presetSaveEvent.detail.data.meta.type);
      preset.setAttribute('structure', JSON.stringify(presetSaveEvent.detail.data));

      if (presetSaveEvent.detail.overwrite) {
        let nameEscaped = presetSaveEvent.detail.name.replace(/"/g, '\\"');
        let existingPresets = this._canvas.contentEditor.querySelectorAll('ropi-content-preset[name="' + nameEscaped + '"]:not([readonly])');
        for (let existingPreset of existingPresets) {
          existingPreset.parentNode.removeChild(existingPreset);
        }
      }

      this._canvas.contentEditor.appendChild(preset);
      this._canvas.contentEditor.update();
    }
  }

  connectedCallback() {
    if (!this._targetSlot.parentNode) {
      this.appendChild(this._targetSlot);
    }

    this._close.addEventListener('click', this._closeHandler);
    this._reload.addEventListener('click', this._reloadHandler);
    this._duplicate.addEventListener('click', this._duplicateHandler);
    this._moveToPreviousPosition.addEventListener('click', this._moveToPreviousPositionHandler);
    this._moveToNextPosition.addEventListener('click', this._moveToNextPositionHandler);
    this._moveToPreviousArea.addEventListener('click', this._moveToPreviousAreaHandler);
    this._moveToNextArea.addEventListener('click', this._moveToNextAreaHandler);
    this._delete.addEventListener('click', this._deleteHandler);
    this._savePreset.addEventListener('click', this._savePresetHandler);

    TranslateElement.bind(this._snippetsChangeHandler);
    this._snippetsChangeHandler();
  }

  disconnectedCallback() {
    TranslateElement.unbind(this._snippetsChangeHandler);
  }

  update() {
    this._updateConfiguration();
    this._updateBreadcrumb();
  }

  set contentElement(contentElement) {
    this._contentElement = contentElement;
    this.update();
  }

  get contentElement() {
    return this._contentElement;
  }

  _updateConfiguration() {
    for (let node of this._targetSlot.assignedNodes()) {
      if (node.parentNode) {
        node.parentNode.removeChild(node);
      }
    }

    if (!this._contentElement) {
      return;
    }

    let configurationElement = this._contentElement.configurationElement;
    if (configurationElement) {
      configurationElement.setAttribute('slot', 'ropi-settings-panel-configuration');
      if (this.contentEditor) {
        this.contentEditor.appendChild(configurationElement);
      }
      this._contentElement.mapConfigurationToElement();
    }
  }

  _updateBreadcrumb() {
    let renderedElement = this._contentElement
                          ? this._contentElement.renderedElement
                          : this._contentElement;

    if (renderedElement == null) {
      this._breadcrumb.textContent = '';
      return;
    }

    this._breadcrumb.textContent = '';

    let renderedElements = DOMUtil.parents(
      renderedElement,
      '[data-ropi-content-element]'
    );

    renderedElements.unshift(renderedElement);

    for (let i = renderedElements.length - 1; i >= 0; i--) {
      let element = renderedElements[i];

      let item = document.createElement('ropi-breadcrumb-item');
      item.textContent = element.ropiContentElement.name;

      if (renderedElement === element) {
        item.setAttribute('active', '');
      }

      this._breadcrumb.appendChild(item);

      item.addEventListener('click', () => {
        if (item.hasAttribute('active')) {
          return;
        }

        if (this._canvas) {
          this._canvas.activeContentElement = element.ropiContentElement;
        }

        this._contentElement = element.ropiContentElement;
        this._updateConfiguration();

        for (let existingItem of this._breadcrumb.children) {
          existingItem.removeAttribute('active');
        }

        item.setAttribute('active', '');

        this._breadcrumb.scrollSmooth(
          null,
          item.offsetLeft
        );
      });
    }

    requestAnimationFrame(() => {
      this._breadcrumb.scrollPosition = this._breadcrumb.maxScrollPosition;
    });
  }
}

RopiSettingsPanelElement._template = html`
<style>
:host {
  display: block;
  transition: transform var(--ropi-animation-duration, 301ms) ease;
  transform: translateX(100%);
  width: calc(100% - 4rem);
  max-width: 22rem;
  background-color: var(--ropi-color-base, black);
  z-index: 30;
  position: absolute;
  right: 0;
  top: 0;
  bottom: 0;
  border-left: solid 0.0625rem var(--ropi-color-contrast-medium, grey);
}

.panel-content {
  overflow-y: auto;
  pointer-events: none;
  position: absolute;
  top: 6rem;
  bottom: 0;
  width: 100%;
}

:host([open]) {
  transform: translateX(0);
  transition-delay: var(--ropi-animation-duration, 301ms);
}

:host([open]) > .panel-content {
  pointer-events: auto;
}

#breadcrumb {
  line-height: 3rem;
  height: 3rem;
  position: absolute;
  left: 3rem;
  right: 3rem;
  bottom: 0;
}

#titlebar,
#actionbar {
  height: 3rem;
  width: 100%;
  position: relative;
}

#actionbar {
  display: flex;
}

#titlebar > ropi-touchable,
#actionbar > ropi-touchable {
  width: 3rem;
  height: 3rem;
  line-height: 3rem;
  text-align: center;
  display: inline-block;
}

#close {
  position: absolute;
  top: 0;
  right: 0;
}

.dialog-text {
  padding: var(--ropi-grid-outer-gutter-height, 0.75rem)
           var(--ropi-grid-outer-gutter-width, 1rem);
  padding-top: 0;
}
</style>
<div id="titlebar">
  <ropi-touchable id="reload">
    <ropi-material-icon>refresh</ropi-material-icon>
  </ropi-touchable>
  <ropi-vertical-scroll id="breadcrumb"></ropi-vertical-scroll>
  <ropi-touchable id="close">
    <ropi-material-icon>arrow_forward</ropi-material-icon>
  </ropi-touchable>
</div>
<div id="actionbar">
  <ropi-touchable id="moveToPreviousPosition">
    <ropi-material-icon>keyboard_arrow_up</ropi-material-icon>
  </ropi-touchable>
  <ropi-touchable id="moveToNextPosition">
    <ropi-material-icon>keyboard_arrow_down</ropi-material-icon>
  </ropi-touchable>
  <ropi-touchable id="moveToPreviousArea">
    <ropi-material-icon>skip_previous</ropi-material-icon>
  </ropi-touchable>
  <ropi-touchable id="moveToNextArea">
    <ropi-material-icon>skip_next</ropi-material-icon>
  </ropi-touchable>
  <ropi-touchable id="duplicate">
    <ropi-material-icon>control_point_duplicate</ropi-material-icon>
  </ropi-touchable>
  <ropi-touchable id="savePreset">
    <ropi-material-icon>save</ropi-material-icon>
  </ropi-touchable>
  <ropi-touchable id="delete">
    <ropi-material-icon>delete</ropi-material-icon>
  </ropi-touchable>
</div>
<div class="panel-content">
  <slot></slot>
</div>
<ropi-dialog id="delete-dialog">
  <div slot="title">
    <ropi-translate>deleteElement</ropi-translate>
  </div>
  <div slot="content">
    <div class="dialog-text">
      <ropi-translate>confirmDeleteElement</ropi-translate>
    </div>
  </div>
  <div slot="primary">
    <ropi-translate>Confirm</ropi-translate>
  </div>
  <div slot="cancel">
    <ropi-translate>Cancel</ropi-translate>
  </div>
</ropi-dialog>
<ropi-dialog id="savePreset-dialog">
  <div slot="title">
    <ropi-translate>savePreset</ropi-translate>
  </div>
  <div slot="content">
    <div class="dialog-text" style="max-width: 320px">
      <ropi-translate>savePresetDialogText</ropi-translate>
    </div>
    <div style="padding-top: var(--ropi-grid-outer-gutter-height, 0.75rem)">
      <ropi-textfield name="presetName">
        <div slot="placeholder"><ropi-translate>presetName</ropi-translate></div>
      </ropi-textfield>
    </div>
  </div>
  <div slot="primary">
    <ropi-translate>Confirm</ropi-translate>
  </div>
  <div slot="cancel">
    <ropi-translate>Cancel</ropi-translate>
  </div>
</ropi-dialog>
<ropi-dialog id="overwritePreset-dialog">
  <div slot="title">
    <ropi-translate>overwritePreset</ropi-translate>
  </div>
  <div slot="content">
    <div class="dialog-text">
      <ropi-translate>confirmOverwritePreset</ropi-translate>
      <br />
      <ropi-translate>confirmOverwritePreset2</ropi-translate>
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

customElements.define('ropi-settings-panel', RopiSettingsPanelElement);

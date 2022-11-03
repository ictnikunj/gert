import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

import Logger from '../logger/logger.js?v=1637255330';
import DOMUtil from '../dom-util/dom-util.js?v=1637255330';
import TypeUtil from '../type-util/type-util.js?v=1637255330';

import '../editable-panel-element/editable-panel-element.js?v=1637255330';
import '../material-icon-element/material-icon-element.js?v=1637255330';
import TranslateElement from '../translate-element/translate-element.js?v=1637255330';

import './canvas-element/canvas-element.js?v=1637255330';
import './content-element-panel-element/content-element-panel-element.js?v=1637255330';
import './settings-panel-element/settings-panel-element.js?v=1637255330';
import './top-panel-element/top-panel-element.js?v=1637255330';

import RopiContentElement from './content-element.js?v=1637255330';
import RopiContentPresetElement from './content-preset-element.js?v=1637255330';

TranslateElement.registerSnippets({
  and: 'and',
  contentElements: 'Content elements',
  Close: 'Close',
  Confirm: 'Confirm',
  Cancel: 'Cancel',
  back: 'Back',
  moveToPreviousArea: 'Move to previous possible area',
  moveToNextArea: 'Move to next possible area',
  moveToPreviousPosition: 'Move to previous position',
  moveToNextPosition: 'Move to next position',
  duplicateElement: 'Duplicate element',
  deleteElement: 'Delete element',
  confirmDeleteElement: 'Are you sure you want to delete this {{element.name}} element?',
  reloadElement: 'Reload element',
  reloadDocument: 'Reload document',
  sites: 'Sites',
  enablePreviewMode: 'Enable preview mode',
  disablePreviewMode: 'Disable preview mode',
  documentActions: 'Document actions',
  openInNew: 'Open URL in new tab',
  save: 'Save',
  saveAndPublish: 'Save and publish',
  unpublish: 'Unpublish',
  versions: 'Versions',
  revertToVersion: 'Revert to version',
  confirmRevertToVersion: 'Are you sure you want to revert to version of {{version.time}}?',
  importDocument: 'Import',
  exportDocument: 'Export',
  fromSite: 'From site',
  fromFile: 'From file',
  keepLanguageSpecificData: 'Keep language specific data',
  importKeepLanguageSpecificDataHintUnchecked: 'All content elements are completely overwritten by the import data.',
  importKeepLanguageSpecificDataHintChecked: 'If a content element has been previously exported/imported, the language-specific data will be kept.',
  deleteDocumentElements: 'Remove all content elements',
  confirmDeleteDocumentElements: 'Are you sure you want to remove all content elements?',
  versionPreviewInNew: 'Show version in new tab',
  logout: 'Logout',
  presets: 'Presets',
  basicElements: 'Basic elements',
  savePreset: 'Save content element as preset',
  savePresetDialogText: 'The complete {{element.name}} element including its child elements and settings is saved as a preset.',
  presetName: 'Preset name',
  presetNameEmpty: 'Preset name is empty',
  overwritePreset: 'Overwrite preset',
  confirmOverwritePreset: "A preset with the name '{{presetName}}' already exists.",
  confirmOverwritePreset2: "Do you want to replace it?",
  presetActions: "Preset actions",
  deletePreset: "Delete preset",
  confirmDeletePreset: "Are you sure you want to delete the preset '{{preset.name}}'?",
  toFile: "To file",
  toSites: "To sites",
  exportToFileText: "When you click 'Confirm', all the content elements of this page will be downloaded as a file, which can then be imported to any other page. Make sure that the target page has the same content areas as this page, otherwise the content elements may not be imported. ",
  exportFileName: 'Filename',
  exportKeepLanguageSpecificDataHintUnchecked: 'All content elements on the target sites are completely overwritten by the export data.',
  exportKeepLanguageSpecificDataHintChecked: 'If a content element has been previously exported/imported on a target site, the language-specific data will be kept.',
  exportPublish: 'Publish content on target sites',
  exportPublishHintUnchecked: 'The exported content elements are not published on the target sites.',
  exportPublishHintChecked: 'The exported content elements are published immediately on the target sites.'
});

export default class RopiContentEditorElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['src', 'breakpoints', 'locked', 'snippets'];
  }

  constructor() {
    super();

    this._contentElementPanel = this.shadowRoot.querySelector('ropi-content-element-panel');
    this._mainArea = this.shadowRoot.getElementById('main-area');
    this._editablePanel = this.shadowRoot.querySelector('ropi-editable-panel');
    this._topPanel = this.shadowRoot.querySelector('ropi-top-panel');
    this._dropAreaDelete = this.shadowRoot.getElementById('drop-area-delete');
    this._canvas = this.shadowRoot.querySelector('ropi-content-editor-canvas');
    this._canvas.contentEditor = this;
    this._settingsPanel = this.shadowRoot.querySelector('ropi-settings-panel');
    this._settingsPanel.contentEditor = this;

    this._canvasSelectElementHandler = (event) => {
      this._closeContentElementPanel();

      this._topPanel.closeMenu();

      if (this._canvas.activeContentElement) {
        this._settingsPanel.contentElement = this._canvas.activeContentElement;
        this._openSettingsPanel();
      } else {
        this._closeSettingsPanel();
      }
    };

    this._canvasLoadHandler = () => {
      this._updateLock();

      if (this._canvas.hasAttribute('error')) {
        return;
      }

      window.removeEventListener('beforeunload', this._beforeunloadHandler);
      window.addEventListener('beforeunload', this._beforeunloadHandler);

      this._canvas.iframe.contentWindow.addEventListener(
        'beforeunload',
        this._beforeunloadHandler
      );

      this.workingDocument.addEventListener(
        'editstart',
        this._workingDocumentEditStartHandler
      );

      this.workingDocument.addEventListener(
        'editend',
        this._workingDocumentEditEndHandler
      );
    };

    this._panelClickHandler = (event) => {
      this._topPanel.closeMenu();
      event.stopPropagation();
    };

    this._clickHandler = (event) => {
      this._closeSidePanels();
    };

    this._contentElementPanelOpenHandler = () => {
      this._closeSettingsPanel();
    };

    this._contentElementDragStartHandler = () => {
      this._topPanel.closeMenu();
      this._closeSidePanels();
      this._dropAreaDelete.classList.add('visible');
      this._mainArea.style.overflow = 'hidden';
    };

    this._contentElementDragEndHandler = () => {
      this._dropAreaDelete.classList.remove('visible');
      this._mainArea.style.overflow = '';
    };

    this._workingDocumentEditStartHandler = (event) => {
      this._closeSidePanels();
      this._contentElementPanel.setAttribute('noflap', '');

      this._editablePanel.htmlFor = event.target;

      let commandList = (event.target.getAttribute('data-ropi-content-editable-commands') || '').trim();
      if (commandList) {
        // hide all commands
        for (let command of this._editablePanel.commands) {
          this._editablePanel.setAttribute('hide' + command.toLowerCase(), '');
        }

        // show only defined commands
        for (let command of commandList.split(',')) {
          this._editablePanel.removeAttribute('hide' + command.trim().toLowerCase());
        }
      } else {
        // show all commands
        for (let command of this._editablePanel.commands) {
          this._editablePanel.removeAttribute('hide' + command.toLowerCase());
        }
      }

      let defaultParagraphSeparator = (event.target.getAttribute('data-ropi-content-editable-defaultParagraphSeparator') || '').trim();
      if (defaultParagraphSeparator) {
        this._editablePanel.htmlFor.ownerDocument.execCommand('defaultParagraphSeparator', false, defaultParagraphSeparator);
      } else {
        this._editablePanel.htmlFor.ownerDocument.execCommand('defaultParagraphSeparator', false, 'div');
      }

      this._editablePanel.classList.add('open');
    };

    this._workingDocumentEditEndHandler = (event) => {
      let defaultParagraphSeparator = (event.target.getAttribute('data-ropi-content-editable-defaultParagraphSeparator') || '').trim();
      //event.target.innerHTML = event.target.innerHTML.replace(/<p>\s*<\/p>/g, '');
      event.target.innerHTML = event.target.innerHTML.replace(
          new RegExp('<' + 'defaultParagraphSeparator' + '>\\s*<\\/' + defaultParagraphSeparator + '>', 'g'),
          ''
      );

      this._contentElementPanel.removeAttribute('noflap');

      this._closeSidePanels();
      this._closeEditablePanel();
    };

    this._editablePanelActionHandler = (event) => {
      let editableElement = this._editablePanel.htmlFor;
      if (!editableElement) {
        return;
      }

      this._editablePanel.style.transitionDelay = '0ms';
      this._topPanel.style.transitionDelay = '0ms';
      DOMUtil.forceReflow([this._editablePanel, this._topPanel]);

      requestAnimationFrame(() => {
        if (event.detail.cancel && editableElement.ropiEditable) {
          editableElement.ropiEditable.discardChanges();
        } else {
          editableElement.blur();
        }

        this._editablePanel.style.transitionDelay = '';
        this._topPanel.style.transitionDelay = '';
      });
    };

    this._beforeunloadHandler = (event) => {
      if (this._canvas.hasAttribute('error')) {
        return;
      }

      event.preventDefault();
      event.returnValue = '';
    };
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (name === 'src') {
      if (this.hasAttribute('src')) {
        this._canvas.iframe.setAttribute('src', value);
      } else {
        this._canvas.iframe.removeAttribute('src', value);
      }
    } else if (name === 'breakpoints') {
      if (this.hasAttribute('breakpoints')) {
        try {
          let breakpoints = JSON.parse(this.getAttribute('breakpoints'));
          if (TypeUtil.isObject(breakpoints)) {
            this.breakpoints = breakpoints;
          }
        } catch(e) {
          this.breakpoints = {};
        }
      } else {
        this.breakpoints = {};
      }
    } else if (name === 'locked') {
      this._updateLock();
    } else if (name === 'snippets') {
      if (this.hasAttribute('snippets')) {
        try {
          let snippets = JSON.parse(this.getAttribute('snippets'));
          if (TypeUtil.isObject(snippets)) {
            TranslateElement.registerSnippets(snippets);
          }
        } catch (e) {
          // Silent fail
        }
      }
    }
  }

  connectedCallback() {
    this._canvas.addEventListener('selectelement', this._canvasSelectElementHandler);
    this._canvas.addEventListener('load', this._canvasLoadHandler);

    this._contentElementPanel.addEventListener(
      'open',
      this._contentElementPanelOpenHandler
    );

    this._contentElementPanel.addEventListener(
      'contentelementdragstart',
      this._contentElementDragStartHandler
    );

    this._contentElementPanel.addEventListener(
      'contentelementdragend',
      this._contentElementDragEndHandler
    );

    this._settingsPanel.addEventListener('click', this._panelClickHandler);
    this._contentElementPanel.addEventListener('click', this._panelClickHandler);
    this.shadowRoot.addEventListener('click', this._clickHandler);

    this._editablePanel.addEventListener('action', this._editablePanelActionHandler);

    this._contentElementPanel.contentElements = this.contentElements;
    this._contentElementPanel.contentPresets = this.contentPresets;
  }

  disconnectedCallback() {
    this._canvas.removeEventListener('selectelement', this._canvasSelectElementHandler);
    this._canvas.removeEventListener('load', this._canvasLoadHandler);

    this._contentElementPanel.removeEventListener(
      'open',
      this._contentElementPanelOpenHandler
    );

    this._contentElementPanel.removeEventListener(
      'contentelementdragstart',
      this._contentElementDragStartHandler
    );

    this._contentElementPanel.removeEventListener(
      'contentelementdragend',
      this._contentElementDragEndHandler
    );

    window.removeEventListener('beforeunload', this._beforeunloadHandler);

    this._contentElementPanel.removeEventListener('click', this._panelClickHandler);
    this._settingsPanel.removeEventListener('click', this._panelClickHandler);
    this.shadowRoot.removeEventListener('click', this._clickHandler);

    this._editablePanel.removeEventListener('action', this._editablePanelActionHandler);
  }

  update() {
    this._contentElementPanel.contentElements = this.contentElements;
    this._contentElementPanel.contentPresets = this.contentPresets;
    this._contentElementPanel.update();
  }

  reload() {
    this._canvas.iframe.contentWindow.removeEventListener(
      'beforeunload',
      this._beforeunloadHandler
    );

    this.workingDocument.location.reload();

    this._canvas.iframe.contentWindow.addEventListener(
      'beforeunload',
      this._beforeunloadHandler
    );
  }

  get sites() {
    let sites = [];

    try {
      sites = this._canvas.contentEditor.getAttribute('sites');
      sites = sites ? JSON.parse(sites) : null;

      if (TypeUtil.isArray(sites)) {
        for (let site of sites) {
          let a = document.createElement('a');
          a.href = site.editorUrl;

          if (!a.host) {
            continue;
          }

          site.host = a.host;
        }
      }
    } catch(e) {
      Logger.logError(
        'Failed to parse sites attribute from content editor element: ',
        this,
        'The error was: ',
        error
      );
    }

    return TypeUtil.isArray(sites) ? sites : [];
  }

  get mainSites() {
    let mainSites = {};

    for (let site of this.sites) {
      if (!site.host) {
        continue;
      }

      if (!mainSites[site.host]) {
        mainSites[site.host] = site;
        continue;
      }

      if (site.editorUrl.length < mainSites[site.host].editorUrl.length) {
        mainSites[site.host] = site;
      }
    }

    return Object.values(mainSites);
  }

  set breakpoints(breakpoints) {
    this._topPanel.breakpoints = breakpoints;
  }

  get breakpoints() {
    return this._topPanel.breakpoints;
  }

  get workingDocument() {
    return this._canvas.workingDocument;
  }

  get contentElements() {
    let contentElements = [];

    for (let child of this.children) {
      if (child instanceof RopiContentElement) {
        contentElements.push(child);
      }
    }

    return contentElements;
  }

  get contentPresets() {
    let contentPresets = [];

    for (let child of this.children) {
      if (child instanceof RopiContentPresetElement) {
        contentPresets.push(child);
      }
    }

    return contentPresets;
  }

  _updateLock() {
    if (this.hasAttribute('locked')) {
      this._lock();
    } else {
      this._unlock();
    }
  }

  _lock() {
    let layer = this.workingDocument.getElementById('ropi-content-editor-lock-layer');

    if (layer) {
      return;
    }

    layer = this.workingDocument.createElement('div');
    layer.setAttribute('id', 'ropi-content-editor-lock-layer');
    layer.style.zIndex = '2147483647';
    layer.style.position = 'fixed';
    layer.style.left = '0';
    layer.style.top = '0';
    layer.style.width = '100%';
    layer.style.height = '100%';
    layer.style.backgroundColor = 'black';
    layer.style.opacity = '0.5';

    this.workingDocument.body.appendChild(layer);
  }

  _unlock() {
    let layer = this.workingDocument.getElementById('ropi-content-editor-lock-layer');

    if (layer) {
      layer.parentNode.removeChild(layer);
    }
  }

  _closeSidePanels() {
    this._closeContentElementPanel();
    this._closeSettingsPanel();
  }

  _closeEditablePanel() {
    this._editablePanel.classList.remove('open');
  }

  _closeContentElementPanel() {
    this._contentElementPanel.removeAttribute('open');
  }

  _closeSettingsPanel() {
    this._settingsPanel.removeAttribute('open');
  }

  _openSettingsPanel() {
    if (this._settingsPanel.hasAttribute('open')) {
      return;
    }

    this._settingsPanel.setAttribute('open', '');
  }
}

RopiContentEditorElement._template = html`
<style>
  :host {
    background-color: var(--ropi-color-base, black);
    display: block;
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    right: 0;
    overflow: hidden;
  }

  #drop-area-delete {
    height: 6rem;
    position: absolute;
    z-index: 11;
    width: 100%;
    left: 0;
    top: 0;
    display: none;
    background-color: inherit;
  }

  #drop-area-delete > ropi-material-icon {
    width: 3rem;
    height: 3rem;
    position: absolute;
    left: 50%;
    top: 50%;
    margin-top: -1.5rem;
    margin-left: -1.5rem;
    pointer-events: none;
  }

  #drop-area-delete.dragover {
    color: var(--ropi-color-red, red);
  }

  #drop-area-delete.visible {
    display: block;
    z-index: 11;
  }

  #main-area {
    position: absolute;
    top: calc(6rem + 1px);
    bottom: 1px;
    left: 1px;
    right: 1px;
    overflow: auto;
    background-color: var(--ropi-color-base, base);
  }

  ropi-touchable[disabled] {
    opacity: var(--ropi-disabled-opacity, 0.33);
  }

  #top-area {
    position: absolute;
    height: 6rem;
    width: 100%;
  }

  ropi-editable-panel {
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    transition: transform var(--ropi-animation-duration, 301ms) ease;
    transition-delay: var(--ropi-animation-duration, 301ms);
    transform: translateY(-100%);
    pointer-events: none;
    z-index: 1;
  }

  ropi-editable-panel.open {
    transition-delay: 0ms;
    transform: translateY(0);
    pointer-events: all;
  }

  ropi-top-panel {
    transition: opacity var(--ropi-animation-duration, 301ms) ease;
    transition-delay: var(--ropi-animation-duration, 301ms);
    opacity: 1;
  }

  ropi-editable-panel.open + ropi-top-panel {
    transition-delay: 0ms;
    opacity: 0;
    pointer-events: none;
  }

  #canvas {
    transition: transform var(--ropi-animation-duration, 301ms) ease;
    transition-delay: var(--ropi-animation-duration, 301ms);
  }
</style>
<ropi-content-element-panel canvas="canvas"></ropi-content-element-panel>
<ropi-settings-panel canvas="canvas"></ropi-settings-panel>
<div id="top-area">
  <ropi-editable-panel></ropi-editable-panel>
  <ropi-top-panel canvas="canvas"></ropi-top-panel>
</div>
<div id="drop-area-delete">
  <ropi-material-icon>delete</ropi-material-icon>
</div>
<div id="main-area">
  <ropi-content-editor-canvas id="canvas" editmode></ropi-content-editor-canvas>
</div>
`;

customElements.define('ropi-content-editor', RopiContentEditorElement);

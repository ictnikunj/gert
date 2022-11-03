import RopiHTMLElement from '../../html-element/html-element.js?v=1637255330';
import html from '../../html-tag/html-tag.js?v=1637255330';

import '../../touchable-element/touchable-element.js?v=1637255330';
import '../../textfield-element/textfield-element.js?v=1637255330';
import '../../material-icon-element/material-icon-element.js?v=1637255330';
import '../../tabs-element/tabs-element.js?v=1637255330';
import '../../for-element/for-element.js?v=1637255330';
import '../../if-element/if-element.js?v=1637255330';
import '../../menu-element/menu-element.js?v=1637255330';
import '../../hline-element/hline-element.js?v=1637255330';
import '../../checkbox-element/checkbox-group-element.js?v=1637255330';
import '../../checkbox-element/checkbox-element.js?v=1637255330';
import '../../radio-element/radio-group-element.js?v=1637255330';
import '../../radio-element/radio-element.js?v=1637255330';
import '../../hint-element/hint-element.js?v=1637255330';
import '../../toast-element/toast-element.js?v=1637255330';

import TypeUtil from '../../type-util/type-util.js?v=1637255330';
import DataParser from '../data-parser/data-parser.js?v=1637255330';
import TranslateElement from '../../translate-element/translate-element.js?v=1637255330';
import DialogElement from '../../dialog-element/dialog-element.js?v=1637255330';
import Logger from '../../logger/logger.js?v=1637255330';

export default class RopiTopPanelElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['canvas'];
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (name === 'canvas') {
      if (this._canvas) {
        this._canvas.removeEventListener('load', this._canvasLoadHandler);
      }

      this._canvas = value
                     ? this.getRootNode().getElementById(value)
                     : null;

      if (this._canvas) {
        this._canvas.addEventListener('load', this._canvasLoadHandler);
      }
    }
  }

  constructor() {
    super();

    this._canvas = null;
    this._breakpoints = [];

    this._urlInput = this.shadowRoot.getElementById('url-input');
    this._backButton = this.shadowRoot.getElementById('back-button');
    this._sitesButton = this.shadowRoot.getElementById('sites-button');
    this._togglePreviewButton = this.shadowRoot.getElementById('toggle-preview-button');
    this._moreButton = this.shadowRoot.getElementById('more-button');
    this._reloadButton = this.shadowRoot.getElementById('reload-button');
    this._logoutButton = this.shadowRoot.getElementById('logout-button')
    this._openInNewButton = this.shadowRoot.getElementById('open-in-new-button');
    this._saveButton = this.shadowRoot.getElementById('save-button');
    this._publishButton = this.shadowRoot.getElementById('publish-button');
    this._unpublishButton = this.shadowRoot.getElementById('unpublish-button');
    this._versionsButton = this.shadowRoot.getElementById('versions-button');
    this._deleteButton = this.shadowRoot.getElementById('delete-button');
    this._exportButton = this.shadowRoot.getElementById('export-button');
    this._exportDialog = this.shadowRoot.getElementById('export-dialog');
    this._exportTabs = this.shadowRoot.getElementById('export-tabs');
    this._exportToSitesFor = this.shadowRoot.getElementById('export-to-sites-for');
    this._exportToSitesGroup = this.shadowRoot.getElementById('export-to-sites-group');
    this._exportKeepLanguageSpecificDataCheckbox = this.shadowRoot.getElementById('export-keep-language-specific-data-checkbox');
    this._exportPublishCheckbox = this.shadowRoot.getElementById('export-publish-checkbox');
    this._exportToSitesTab = this.shadowRoot.getElementById('export-to-sites-tab');
    this._importButton = this.shadowRoot.getElementById('import-button');
    this._importDialog = this.shadowRoot.getElementById('import-dialog');
    this._importTabs = this.shadowRoot.getElementById('import-tabs');
    this._importFile = this.shadowRoot.getElementById('import-file');
    this._importSitesFor = this.shadowRoot.getElementById('import-sites-for');
    this._importSitesGroup = this.shadowRoot.getElementById('import-sites-group');
    this._importFromSiteTab = this.shadowRoot.getElementById('import-from-site-tab');
    this._importKeepLanguageSpecificDataCheckbox = this.shadowRoot.getElementById('import-keep-language-specific-data-checkbox');
    this._versionsPanel = this.shadowRoot.getElementById('versions-panel');
    this._versionsFor = this.shadowRoot.getElementById('versions-for');
    this._deleteDialog = this.shadowRoot.getElementById('delete-dialog');
    this._revertToVersionDialog = this.shadowRoot.getElementById('revert-to-version-dialog');
    this._versionsBackButton = this.shadowRoot.getElementById('versions-back-button');
    this._breakpointTabs = this.shadowRoot.getElementById('breakpoint-tabs');
    this._menu = this.shadowRoot.getElementById('menu');
    this._sitesFor = this.shadowRoot.getElementById('sites-for');
    this._sitesMenu = this.shadowRoot.getElementById('sites-menu');

    this._snippetsChangeHandler = () => {
      this._backButton.setAttribute('title', TranslateElement.translate('back'));
      this._moreButton.setAttribute('title', TranslateElement.translate('documentActions'));
      this._togglePreviewButton.setAttribute('title', TranslateElement.translate('enablePreviewMode'));
      this._sitesButton.setAttribute('title', TranslateElement.translate('sites'));

      let versionPreviews = this._versionsPanel.querySelectorAll('.version-preview');
      for (let versionPreview of versionPreviews) {
        versionPreview.setAttribute('title', TranslateElement.translate('versionPreviewInNew'));
      }

      if (this._canvas.hasAttribute('editmode')) {
        this._togglePreviewButton.setAttribute('title', TranslateElement.translate('enablePreviewMode'));
      } else {
        this._togglePreviewButton.setAttribute('title', TranslateElement.translate('disablePreviewMode'));
      }
    };

    this._canvasLoadHandler = () => {
      let documentContext = this._canvas.documentContext;
      this._togglePreviewButton.setAttribute('disabled', '');

      for (let touchable of this._menu.querySelectorAll('ropi-touchable')) {
        touchable.setAttribute('disabled', '');
      }

      this._logoutButton.removeAttribute('disabled');

      if (!this._urlInput.focused) {
        if (this._canvas.hasAttribute('error')) {
          this._urlInput.value = 'about:blocked';
        } else {
          let relPath = this._canvas.workingDocument.location.href;
          relPath = relPath.substring(this._canvas.workingDocument.location.origin.length);
          this._urlInput.value = relPath;
        }
      }

      if (this._canvas.hasAttribute('error')) {
        return;
      }

      this._reloadButton.removeAttribute('disabled');

      if (!documentContext) {
        return;
      }

      this._togglePreviewButton.removeAttribute('disabled');

      for (let touchable of this._menu.querySelectorAll('ropi-touchable')) {
        touchable.removeAttribute('disabled');
      }

      this._canvas.contentEditor.removeEventListener('dropaction', this._dropactionHandler);
      this._canvas.contentEditor.addEventListener('dropaction', this._dropactionHandler);

      this._unpublishButton.setAttribute('disabled', '');
      for (let version of this._canvas.documentVersions) {
        if (version.published) {
          this._unpublishButton.removeAttribute('disabled');
        }
      }

      if (this._canvas.documentVersions.length > 0) {
        this._versionsButton.removeAttribute('disabled');
        this._versionsFor.each = this._canvas.documentVersions;
        let versions = this._versionsPanel.querySelectorAll('.version');
        for (let version of versions) {
          version.addEventListener('click', this._versionClickHandler);
        }

        let versionPreviews = this._versionsPanel.querySelectorAll('.version-preview');
        for (let versionPreview of versionPreviews) {
          versionPreview.addEventListener('click', this._versionPreviewClickHandler);
        }
      } else {
        this._versionsButton.setAttribute('disabled', '');
        this._versionsFor.each = [];
      }
    };

    this._urlInputKeydownHandler = (event) => {
      if (event.keyCode === 13) {
        let src = event.currentTarget.value.trim();

        if (src.indexOf(location.origin) !== 0) {
          src = location.origin + '/' + src.replace(/^\//, '');
        }

        this._canvas.src = src;
        event.currentTarget.blur();
      }
    };

    this._reloadButtonClickHandler = (event) => {
      if (this._reloadButton.hasAttribute('disabled')) {
        return;
      }

      this._canvas.reload();
      this.closeMenu();
    };

    this._backButtonClickHandler = () => {
      this._canvas.historyBack();
    };

    this._sitesButtonClickHandler = (event) => {
      if (this._sitesButton.hasAttribute('disabled')) {
        return;
      }

      let sites = this._canvas.contentEditor.mainSites;

      this._sitesFor.oniterate = (event) => {
        let editorUrl = event.detail.value.editorUrl.trim();
        let touchable = event.detail.elements[0];

        if (editorUrl.indexOf(location.origin) === 0) {
          touchable.setAttribute('disabled', '');
        } else {
          touchable.removeAttribute('disabled');
        }

        touchable.onclick = () => {
          if (touchable.hasAttribute('disabled')) {
            return;
          }

          location.href = editorUrl;
        };
      };

      this._sitesFor.each = sites;

      this._sitesMenu.toggleAttribute('open');
    };

    this._moreButtonClickHandler = (event) => {
      if (this._moreButton.hasAttribute('disabled')) {
        return;
      }

      if (this._menu.hasAttribute('open')) {
        this.closeMenu();
      } else {
        this._menu.setAttribute('open', '');
      }
    };

    this._openInNewButtonClickHandler = () => {
      if (this._openInNewButton.hasAttribute('disabled')) {
        return;
      }

      let versionId = '';

      let latestVersion = this._canvas.documentVersions[0] || null;
      if (latestVersion && latestVersion.id) {
        versionId = latestVersion.id;
      }

      let href = this._getCleanCanvasSrc();
      if (href.indexOf('?') === -1) {
        href += '?';
      } else {
        href += '&';
      }

      href += 'ropi-content-editor-version=' + encodeURIComponent(versionId);

      window.open(href, '_blank');
      this.closeMenu();
    }

    this._saveButtonClickHandler = (event) => {
      if (event.currentTarget.hasAttribute('disabled')) {
        return;
      }

      this.dispatchEvent(new CustomEvent('save', {
        detail: {
          publish: event.currentTarget.id === 'publish-button',
          data: this._canvas.data
        },
        composed: true
      }));

      this.closeMenu();
    };

    this._unpublishButtonClickHandler = () => {
      if (this._unpublishButton.hasAttribute('disabled')) {
        return;
      }

      this.dispatchEvent(new CustomEvent('unpublish', {
        detail: {
          data: this._canvas.data
        },
        composed: true
      }));

      this.closeMenu();
    };

    this._exportButtonClickHandler = (event) => {
      if (this._exportButton.hasAttribute('disabled')) {
        return;
      }

      this.closeMenu();

      let sites = this._getSites();

      this._exportToSitesFor.oniterate = (event) => {
        let isCurrentContext = event.detail.value.isCurrentContext;
        let checkbox = event.detail.elements[0];

        if (isCurrentContext) {
          checkbox.setAttribute('disabled', '');
        } else {
          checkbox.removeAttribute('disabled');
        }
      };

      this._exportToSitesFor.each = sites;

      if (sites.length > 1) {
        this._exportToSitesTab.removeAttribute('disabled');
        this._exportToSitesGroup.uncheckAll();
      } else {
        this._exportToSitesTab.setAttribute('disabled', '');
      }

      let fileNameInput = this._exportDialog.querySelector('[name="exportFileName"]');

      let now = new Date();
      let defaultFileName = ([
        'content-export',
        now.getFullYear(),
        (now.getMonth() + 1).toString().padStart(2, '0'),
        now.getDate().toString().padStart(2, '0')
      ]).join('-');

      fileNameInput.value = defaultFileName;

      this._exportTabs.setActiveByIndex(0, true);

      this._exportDialog.setAttribute('open', '');

      this._exportDialog.ondialogopencomplete = () => {
        this._exportTabs.update();
      };

      this._exportDialog.ondialogclose = (event) => {
        if (event.detail.action !== DialogElement.ACTION_PRIMARY) {
          return;
        }

        if (this._exportTabs.activeTabIndex === 0) {
            this.dispatchEvent(new CustomEvent('exporttofile', {
                detail: {
                  filename: (fileNameInput.value.replace(/\.json/g, '').trim() || defaultFileName) + '.json',
                  data: this._canvas.data
                },
                composed: true
            }));
        } else {
            let documentContextDeltas = [];

            for (let documentContextDelta of this._exportToSitesGroup.checkedValues) {
              documentContextDeltas.push(JSON.parse(documentContextDelta));
            }

            this.dispatchEvent(new CustomEvent('exporttosites', {
                detail: {
                    data: this._canvas.data,
                    documentContextDeltas: documentContextDeltas,
                    keepLanguageSpecificData: this._exportKeepLanguageSpecificDataCheckbox.checked,
                    publish: this._exportPublishCheckbox.checked
                },
                composed: true
            }));
        }
      };
    };

    this._importButtonClickHandler = (event) => {
      if (this._importButton.hasAttribute('disabled')) {
        return;
      }

      this.closeMenu();

      let sites = this._getSites();

      this._importSitesFor.oniterate = (event) => {
        let isCurrentContext = event.detail.value.isCurrentContext;
        let radio = event.detail.elements[0];

        if (isCurrentContext) {
          radio.setAttribute('disabled', '');
        } else {
          radio.removeAttribute('disabled');
        }
      };

      this._importSitesFor.each = sites;

      if (sites.length > 1) {
        this._importFromSiteTab.removeAttribute('disabled');
        this._importSitesGroup.checkFirst();
      } else {
        this._importFromSiteTab.setAttribute('disabled', '');
      }

      this._importTabs.setActiveByIndex(0, true);

      this._importDialog.setAttribute('open', '');

      this._importDialog.ondialogopencomplete = () => {
        this._importTabs.update();
      };

      this._importDialog.ondialogclose = (event) => {
        if (event.detail.action !== DialogElement.ACTION_PRIMARY) {
          return;
        }

        if (this._importTabs.activeTabIndex === 0) {
          if (this._importFile.files.length === 0) {
            event.preventDefault();
            this._importFile.click();
            return;
          }

          let file = this._importFile.files[0];
          if (file.size > 512000) {
            event.preventDefault();
            this._pushImportError('Import file is too large (max 512 kb)');
            return;
          }

          if (file.type !== 'text/plain' && file.type !== 'application/json') {
            event.preventDefault();
            this._pushImportError("Import file is invalid (must be JSON file)");
            return;
          }

          let reader = new FileReader();

          reader.onerror = (event) => {
            this._pushImportError("Failed to read import file");
          };

          reader.onload = (event) => {
            this.dispatchEvent(new CustomEvent('importfile', {
              detail: {
                data: this._canvas.data,
                fileData: event.target.result,
                keepLanguageSpecificData: this._importKeepLanguageSpecificDataCheckbox.checked
              },
              composed: true
            }));
          };

          reader.readAsText(file);
        } else {
          let documentContextDelta = JSON.parse(this._importSitesGroup.checkedValue) || {};

          this.dispatchEvent(new CustomEvent('importsite', {
            detail: {
              data: this._canvas.data,
              documentContextDelta: documentContextDelta,
              sourceDocumentContext: Object.assign(this._canvas.data.meta.context, documentContextDelta),
              keepLanguageSpecificData: this._importKeepLanguageSpecificDataCheckbox.checked
            },
            composed: true
          }));
        }

        this._importFile.value = '';
      };
    };

    this._versionsButtonClickHandler = () => {
      if (this._versionsButton.hasAttribute('disabled')) {
        return;
      }

      this._versionsPanel.classList.add('open');
    };

    this._versionsBackButtonClickHandler = () => {
      this._versionsPanel.classList.remove('open');
    };

    this._versionClickHandler = (event) => {
      if (this._versionsButton.hasAttribute('disabled')) {
        return;
      }

      let versionId = event.currentTarget.getAttribute('data-id');
      let versionTime = event.currentTarget.getAttribute('data-time');

      this.closeMenu();

      this._revertToVersionDialog.querySelector('.dialog-text ropi-translate').setAttribute('vars', JSON.stringify({
        version: {
          id: versionId,
          time: versionTime
        }
      }));

      this._revertToVersionDialog.setAttribute('open', '');

      this._revertToVersionDialog.ondialogclose = (event) => {
        if (event.detail.action === DialogElement.ACTION_PRIMARY) {
          this.dispatchEvent(new CustomEvent('revert', {
            detail: {
              data: this._canvas.data,
              versionId: versionId
            },
            composed: true
          }));
        }
      };
    };

    this._versionPreviewClickHandler = (event) => {
      let versionId = event.currentTarget.getAttribute('data-id');

      let href = this._getCleanCanvasSrc();
      if (href.indexOf('?') === -1) {
        href += '?';
      } else {
        href += '&';
      }

      href += 'ropi-content-editor-version=' + encodeURIComponent(versionId);

      window.open(href, '_blank');
    };

    this._deleteButtonClickHandler = (event) => {
      if (event.currentTarget.hasAttribute('disabled')) {
        return;
      }

      this.closeMenu();
      this._deleteDialog.setAttribute('open', '');

      this._deleteDialog.ondialogclose = (event) => {
        if (event.detail.action === DialogElement.ACTION_PRIMARY) {
          this._canvas.removeContentElements();
        }
      };
    };

    this._logoutButtonHandler = (event) => {
      if (event.currentTarget.hasAttribute('disabled')) {
        return;
      }

      this.dispatchEvent(new CustomEvent('logout', {
        composed: true
      }));

      this.closeMenu();
    };

    this._togglePreviewButtonClickHandler = (event) => {
      if (this._togglePreviewButton.hasAttribute('disabled')) {
        return;
      }

      if (this._canvas.hasAttribute('editmode')) {
        this.disableEditMode();
      } else {
        this.enableEditMode();
      }
    };

    this._breakpointTabchangeHandler = (event) => {
      this._breakpointWidth = event.detail.tabElement.getAttribute('data-breakpoint-width');
      this._resizeHandler();
    };

    this._resizeHandler = () => {
      if (TypeUtil.isString(this._breakpointWidth)) {
        this._canvas.style.width = this._breakpointWidth;

        if (this._canvas.offsetWidth < window.innerWidth) {
          this._canvas.style.left = (window.innerWidth * 0.5 - this._canvas.offsetWidth * 0.5) + 'px';
        } else {
          this._canvas.style.left = '';
        }
      } else {
        this._canvas.style.width = '';
        this._canvas.style.left = '';
      }
    };

    this._dropactionHandler = (event) => {
      if (event.detail.isNew) {
        this.enableEditMode();
      }
    };
  }

  _getSites() {
    let sites = this._canvas.contentEditor.sites;

    let data = this._canvas.data;
    let currentContext = {};
    if (data.meta && data.meta.context) {
      currentContext = data.meta.context;
    }

    let cleanSites = [];

    for (let site of sites) {
      let documentContextDelta = site.documentContextDelta || {};
      site.documentContextDeltaString = JSON.stringify(documentContextDelta);

      let contextKeys = Object.keys(documentContextDelta);
      if (contextKeys.length <= 0) {
        Logger.logWarning("documentContextDelta property is empty or not set for site: ", site);
        continue;
      }

      site.isCurrentContext = true;

      for (let key of contextKeys) {
        if (documentContextDelta[key] !== currentContext[key]) {
          site.isCurrentContext = false;
          break;
        }
      }

      cleanSites.push(site);
    }

    return cleanSites;
  }

  _pushImportError(message) {
    let toast = document.createElement('ropi-toast');
    toast.setAttribute('severity', 'error');
    toast.innerText = message;
    this.shadowRoot.getElementById('import-container').appendChild(toast);
  }

  _getCleanCanvasSrc() {
    let href = this._canvas.workingDocument.location.href;
    href = href.replace(/ropi-content-editor-version=[a-zA-Z0-9-_%]+&?/g, '');
    return href;
  }

  disableEditMode() {
    let icon = this.shadowRoot.querySelector('#toggle-preview-button > ropi-material-icon');
    icon.textContent = 'visibility_off';
    this._canvas.removeAttribute('editmode');

    this._togglePreviewButton.setAttribute('title', TranslateElement.translate('disablePreviewMode'));
  }

  enableEditMode() {
    let icon = this.shadowRoot.querySelector('#toggle-preview-button > ropi-material-icon');
    icon.textContent = 'visibility';
    this._canvas.setAttribute('editmode', '');

    this._togglePreviewButton.setAttribute('title', TranslateElement.translate('enablePreviewMode'));
  }

  connectedCallback() {
    this._urlInput.addEventListener('keydown', this._urlInputKeydownHandler);
    this._reloadButton.addEventListener('click', this._reloadButtonClickHandler);
    this._backButton.addEventListener('click', this._backButtonClickHandler);
    this._openInNewButton.addEventListener('click', this._openInNewButtonClickHandler);
    this._saveButton.addEventListener('click', this._saveButtonClickHandler);
    this._deleteButton.addEventListener('click', this._deleteButtonClickHandler);
    this._logoutButton.addEventListener('click', this._logoutButtonHandler);
    this._publishButton.addEventListener('click', this._saveButtonClickHandler);
    this._unpublishButton.addEventListener('click', this._unpublishButtonClickHandler);
    this._exportButton.addEventListener('click', this._exportButtonClickHandler);
    this._importButton.addEventListener('click', this._importButtonClickHandler);
    this._versionsButton.addEventListener('click', this._versionsButtonClickHandler);
    this._versionsBackButton.addEventListener('click', this._versionsBackButtonClickHandler);
    this._moreButton.addEventListener('click', this._moreButtonClickHandler);
    this._sitesButton.addEventListener('click', this._sitesButtonClickHandler);
    this._togglePreviewButton.addEventListener('click', this._togglePreviewButtonClickHandler);
    this._breakpointTabs.addEventListener('tabchange', this._breakpointTabchangeHandler);

    window.addEventListener('resize', this._resizeHandler);

    TranslateElement.bind(this._snippetsChangeHandler);
    this._snippetsChangeHandler();
  }

  disconnectedCallback() {
    window.removeEventListener('resize', this._resizeHandler);

    TranslateElement.unbind(this._snippetsChangeHandler);
  }

  closeMenu() {
    this._menu.removeAttribute('open');
    this._sitesMenu.removeAttribute('open');
    this._versionsPanel.classList.remove('open');
  }

  get data() {
    if (!this._canvas) {
      return null;
    }

    return DataParser.parseFromDocument(this._canvas.workingDocument);
  }

  get documentContext() {
    if (!this._canvas) {
      return null;
    }

    return DataParser.parseDocumentContext(this._canvas.workingDocument, true);
  }

  set breakpoints(breakpoints) {
    this._breakpoints = TypeUtil.isTraversable(breakpoints) ? breakpoints : [];

    let forElement = this.shadowRoot.querySelector('ropi-for[as="breakpoint"]');
    forElement.each = this._breakpoints;
  }

  get breakpoints() {
    return this._breakpoints;
  }
}

RopiTopPanelElement._template = html`
<style>
:host {
  display: block;
}

.topbar {
  display: flex;
  line-height: 3rem;
  vertical-align: middle;
  height: 3rem;
}

#url-input {
  --ropi-textfield-padding: 0 0.5rem;
  flex: 1;
  padding: 0.5rem 0.5rem 0 0.5rem;
  height: 2rem;
  line-height: 2rem;
}

.actions::after {
  content: " ";
  font-size: 0;
  clear: both;
}

.actions > ropi-touchable {
  width: 3rem;
  height: 3rem;
  text-align: center;
  float: left;
}

ropi-touchable[disabled] {
  opacity: var(--ropi-disabled-opacity, 0.33);
}

#breakpoint-tabs {
  position: absolute;
  top: 3rem;
  height: 3rem;
  overflow: hidden;
  left: 0;
  right: 0;
  z-index: 0;
}

#breakpoint-tabs.locked::after {
  content: " ";
  z-index: 3;
  position: absolute;
  width: 100%;
  height: 100%;
  left: 0;
  top: 0;
}

ropi-menu ropi-touchable {
  padding: var(--ropi-grid-outer-gutter-height, 0.75rem)
           var(--ropi-grid-outer-gutter-width, 1rem);
}

#menu ropi-material-icon,
#menu .icon-label {
  padding-right: var(--ropi-grid-outer-gutter-height, 0.75rem);
}

#menu .icon-label {
  display: inline-block;
  width: 1.5rem;
  height: 1.5rem;
  line-height: 1.5rem;
  vertical-align: middle;
  text-align: center;
}

.hinted-checkbox .hinted-checkbox-unchecked {
  display: block;
}

.hinted-checkbox .hinted-checkbox-checked {
  display: none;
}

.hinted-checkbox[aria-checked="true"] .hinted-checkbox-unchecked {
  display: none;
}

.hinted-checkbox[aria-checked="true"] .hinted-checkbox-checked {
  display: block;
}

.dialog-text {
  padding: var(--ropi-grid-outer-gutter-height, 0.75rem)
           var(--ropi-grid-outer-gutter-width, 1rem);
  padding-top: 0;
}

#versions-panel {
  display: none;
  background-color: var(--ropi-color-base, black);
  z-index: 1;
}

#versions-panel.open {
  display: block;
}

#versions-panel.open ~ * {
  display: none;
}

#menu .icon-button {
  padding: 0;
  width: 3rem;
  height: 3rem;
  line-height: 3rem;
  text-align: center;
}

#menu .icon-button > ropi-material-icon {
  padding: 0;
}

.panel-title {
  position: absolute;
  height: 3rem;
  line-height: 3rem;
  top: 0;
  left: 3rem;
  right: 3rem;
  text-align: center;
  text-overflow: ellipsis;
}

#menu .version-preview {
  position: relative;
  padding: 0;
  border-left: solid 0.0625rem var(--ropi-color-material-50);
}

#menu .version-preview > ropi-material-icon {
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
}

.version-item {
  border-bottom: solid 0.0625rem var(--ropi-color-material-50);
}

.import-container {
  position: relative;
  width: 90vw;
  height: 70vh;
  max-width: 640px;
  max-height: 20rem;
  display: flex;
  flex-direction: column;
}

.export-container {
  width: calc(90vw - 3rem);
  min-width: 300px;
  max-width: 640px;
  height: 50vh;
  max-height: 25rem;
}

#import-tabs,
#export-tabs {
  position: relative;
}

#import-file {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
}

#import-from-site-tab[disabled],
#export-to-sites-tab[disabled]{
  pointer-events: none;
}

#logout-button {
  color: var(--ropi-color-red, red);
}

</style>
<div class="topbar">
  <div class="actions left">
    <ropi-touchable id="sites-button">
      <ropi-material-icon>arrow_drop_down</ropi-material-icon>
    </ropi-touchable>
    <ropi-touchable id="back-button">
      <ropi-material-icon>arrow_back</ropi-material-icon>
    </ropi-touchable>
  </div>
  <ropi-textfield id="url-input"></ropi-textfield>
  <div class="actions right">
    <ropi-touchable id="toggle-preview-button" disabled>
      <ropi-material-icon>visibility</ropi-material-icon>
    </ropi-touchable>
    <ropi-touchable id="more-button">
      <ropi-material-icon>more_vert</ropi-material-icon>
    </ropi-touchable>
  </div>
</div>
<ropi-menu type="action-left" id="sites-menu">
  <ropi-for as="site" id="sites-for">
    <ropi-touchable>
      <div data-site="host"></div>
    </ropi-touchable>
  </ropi-for>
</ropi-menu>
<ropi-menu type="action-right" id="menu">
  <div id="versions-panel">
    <div>
      <ropi-touchable id="versions-back-button" class="icon-button">
        <ropi-material-icon>arrow_back</ropi-material-icon>
      </ropi-touchable>
      <div class="panel-title">
        <ropi-translate>versions</ropi-translate>
      </div>
    </div>
    <ropi-hline></ropi-hline>
    <ropi-for id="versions-for" as="version">
      <div class="version-item">
        <div style="display: grid; grid-template-columns: auto 3rem">
          <ropi-touchable class="version" data-id="{{version.id}}" data-time="{{version.formattedTime}}">
            <ropi-if condition="{{version.published}}">
              <ropi-material-icon slot="then" style="color: var(--ropi-color-interactive, blue)">check_circle</ropi-material-icon>
              <span class="icon-label" slot="else"></span>
            </ropi-if>
            <span data-version="formattedTime"></span>
            <div style="color: var(--ropi-color-font-50, grey); font-size: var(--ropi-font-size-s, 0.75rem)">
              <span class="icon-label"></span>
              <ropi-material-icon>perm_identity</ropi-material-icon>
              <span data-version="user"></span>
            </div>
            <div style="color: var(--ropi-color-font-50, grey); font-size: var(--ropi-font-size-s, 0.75rem)">
              <span class="icon-label"></span>
              <span class="icon-label" style="font-size: var(--ropi-font-size-m, 1rem)">ID</span>
              <span data-version="id"></span>
            </div>
          </ropi-touchable>
          <ropi-touchable class="version-preview" data-id="{{version.id}}">
            <ropi-material-icon>open_in_new</ropi-material-icon>
          </ropi-touchable>
        </div>
      </div>
    </ropi-for>
  </div>
  <ropi-touchable id="open-in-new-button" disabled>
    <ropi-material-icon>open_in_new</ropi-material-icon>
    <ropi-translate>openInNew</ropi-translate>
  </ropi-touchable>
  <ropi-hline></ropi-hline>
  <ropi-touchable id="save-button" disabled>
    <ropi-material-icon>save</ropi-material-icon>
    <ropi-translate>save</ropi-translate>
  </ropi-touchable>
  <ropi-touchable id="publish-button" disabled>
    <ropi-material-icon>cloud_upload</ropi-material-icon>
    <ropi-translate>saveAndPublish</ropi-translate>
  </ropi-touchable>
  <ropi-touchable id="unpublish-button" disabled>
    <ropi-material-icon>cloud_off</ropi-material-icon>
    <ropi-translate>unpublish</ropi-translate>
  </ropi-touchable>
  <ropi-hline></ropi-hline>
  <ropi-touchable id="export-button" disabled>
    <ropi-material-icon>call_made</ropi-material-icon>
    <ropi-translate>exportDocument</ropi-translate>
  </ropi-touchable>
  <ropi-touchable id="import-button" disabled>
    <ropi-material-icon>call_received</ropi-material-icon>
    <ropi-translate>importDocument</ropi-translate>
  </ropi-touchable>
  <ropi-hline></ropi-hline>
  <ropi-touchable id="delete-button" disabled>
    <ropi-material-icon>delete</ropi-material-icon>
    <ropi-translate>deleteDocumentElements</ropi-translate>
  </ropi-touchable>
  <ropi-touchable id="versions-button" disabled>
    <ropi-material-icon>history</ropi-material-icon>
    <ropi-translate>versions</ropi-translate>
  </ropi-touchable>
  <ropi-touchable id="reload-button">
    <ropi-material-icon>refresh</ropi-material-icon>
    <ropi-translate>reloadDocument</ropi-translate>
  </ropi-touchable>
  <ropi-hline></ropi-hline>
  <ropi-touchable id="logout-button">
    <ropi-material-icon>no_encryption</ropi-material-icon>
    <ropi-translate>logout</ropi-translate>
  </ropi-touchable>
</ropi-menu>
<ropi-tabs scrollable id="breakpoint-tabs">
  <ropi-touchable slot="tab">Auto</ropi-touchable>
  <ropi-for as="breakpoint">
    <ropi-touchable
      slot="tab"
      data-breakpoint-width="{{breakpoint.width}}"
      data-breakpoint="name">
    </ropi-touchable>
  </ropi-for>
</ropi-tabs>
<ropi-dialog id="import-dialog" nopadding>
  <div slot="title">
    <ropi-translate>import</ropi-translate>
  </div>
  <div slot="content">
    <div class="import-container" id="import-container">
      <ropi-tabs id="import-tabs">
        <ropi-touchable slot="tab">
          <ropi-translate>fromFile</ropi-translate>
        </ropi-touchable>
        <ropi-touchable id="import-from-site-tab" slot="tab">
          <ropi-translate>fromSite</ropi-translate>
        </ropi-touchable>
        <div slot="tabpanel">
          <input id="import-file" type="file" accept=".json" />
        </div>
        <div slot="tabpanel">
          <ropi-radio-group id="import-sites-group">
            <ropi-for as="site" id="import-sites-for">
              <ropi-radio value="{{site.documentContextDeltaString}}"><span data-site="name"></span></ropi-radio>
            </ropi-for>
          </ropi-radio-group>
        </div>
      </ropi-tabs>
      <ropi-checkbox id="import-keep-language-specific-data-checkbox" class="hinted-checkbox">
        <ropi-translate>keepLanguageSpecificData</ropi-translate>
        <ropi-hint type="indented">
          <div class="hinted-checkbox-unchecked">
            <ropi-translate>importKeepLanguageSpecificDataHintUnchecked</ropi-translate>
          </div>
          <div class="hinted-checkbox-checked">
            <ropi-translate>importKeepLanguageSpecificDataHintChecked</ropi-translate>
          </div>
        </ropi-hint>
      </ropi-checkbox>
    </div>
  </div>
  <div slot="primary">
    <ropi-translate>Confirm</ropi-translate>
  </div>
  <div slot="cancel">
    <ropi-translate>Cancel</ropi-translate>
  </div>
</ropi-dialog>
<ropi-dialog id="export-dialog" nopadding>
  <div slot="title">
    <ropi-translate>export</ropi-translate>
  </div>
  <div slot="content">
    <div class="export-container" id="export-container">
      <ropi-tabs id="export-tabs">
        <ropi-touchable slot="tab">
          <ropi-translate>toFile</ropi-translate>
        </ropi-touchable>
        <ropi-touchable id="export-to-sites-tab" slot="tab">
          <ropi-translate>toSites</ropi-translate>
        </ropi-touchable>
        <div slot="tabpanel">
          <div class="dialog-text" style="overflow: hidden;
                                        overflow-y: auto;
                                        padding-bottom: 0;
                                        height: 100%;
                                        align-items: center;
                                        display: flex;">
            <div>
              <ropi-textfield name="exportFileName" style="width: auto; margin: 0 var(--ropi-grid-outer-gutter-width, 0)">
                <div slot="placeholder"><ropi-translate>exportFileName</ropi-translate></div>
              </ropi-textfield>
              <ropi-hint><ropi-translate>exportToFileText</ropi-translate></ropi-hint>
            </div>
          </div>
        </div>
        <div slot="tabpanel">
          <div style="display:grid; 
                      grid-template-rows: minmax(6.5rem, auto) min-content;
                      position: absolute;
                      width: 100%;
                      height: 100%;">
            <ropi-checkbox-group id="export-to-sites-group" style="overflow: hidden;overflow-y: auto;">
              <ropi-for as="site" id="export-to-sites-for">
                <ropi-checkbox value="{{site.documentContextDeltaString}}"><span data-site="name"></span></ropi-checkbox>
              </ropi-for>
            </ropi-checkbox-group>
            <div>
              <ropi-hline></ropi-hline>
              <ropi-checkbox-group>
                <ropi-checkbox id="export-publish-checkbox" class="hinted-checkbox">
                  <ropi-translate>exportPublish</ropi-translate>
                  <ropi-hint type="indented">
                    <div class="hinted-checkbox-unchecked">
                      <ropi-translate>exportPublishHintUnchecked</ropi-translate>
                    </div>
                    <div class="hinted-checkbox-checked">
                      <ropi-translate>exportPublishHintChecked</ropi-translate>
                    </div>
                  </ropi-hint>
                </ropi-checkbox>
                <ropi-checkbox id="export-keep-language-specific-data-checkbox" class="hinted-checkbox">
                  <ropi-translate>keepLanguageSpecificData</ropi-translate>
                  <ropi-hint type="indented">
                    <div class="hinted-checkbox-unchecked">
                      <ropi-translate>exportKeepLanguageSpecificDataHintUnchecked</ropi-translate>
                    </div>
                    <div class="hinted-checkbox-checked">
                      <ropi-translate>exportKeepLanguageSpecificDataHintChecked</ropi-translate>
                    </div>
                  </ropi-hint>
                </ropi-checkbox>
              </ropi-checkbox-group>
            </div>
          </div>
        </div>
      </ropi-tabs>
    </div>
  </div>
  <div slot="primary">
    <ropi-translate>Confirm</ropi-translate>
  </div>
  <div slot="cancel">
    <ropi-translate>Cancel</ropi-translate>
  </div>
</ropi-dialog>
<ropi-dialog id="delete-dialog">
  <div slot="title">
    <ropi-translate>deleteDocumentElements</ropi-translate>
  </div>
  <div slot="content">
    <div class="dialog-text">
      <ropi-translate>confirmDeleteDocumentElements</ropi-translate>
    </div>
  </div>
  <div slot="primary">
    <ropi-translate>Confirm</ropi-translate>
  </div>
  <div slot="cancel">
    <ropi-translate>Cancel</ropi-translate>
  </div>
</ropi-dialog>
<ropi-dialog id="revert-to-version-dialog">
  <div slot="title">
    <ropi-translate>revertToVersion</ropi-translate>
  </div>
  <div slot="content">
    <div class="dialog-text">
      <ropi-translate>confirmRevertToVersion</ropi-translate>
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

customElements.define('ropi-top-panel', RopiTopPanelElement);

import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

import Logger from '../logger/logger.js?v=1637255330';
import TypeUtil from '../type-util/type-util.js?v=1637255330';
import DOMUtil from '../dom-util/dom-util.js?v=1637255330';
import InputMapper from '../input-mapper/input-mapper.js?v=1637255330';
import UUID from '../uuid/uuid.js?v=1637255330';
import TranslateElement from '../translate-element/translate-element.js?v=1637255330';

import HttpClient from '../http-client/http-client.js?v=1637255330';
import HttpRequest from '../http-message/http-request.js?v=1637255330';
import HttpError from '../http-message/http-error.js?v=1637255330';

import DataParser from './data-parser/data-parser.js?v=1637255330';

TranslateElement.registerSnippets({
  doubleClickTapToEdit: 'Double click/tap to edit',
});

const fallbackTemplate = html`
<div style="background-color: #999;
  text-align: center;
  color: white;
  font-size: 1rem;
  padding: 1rem;
  font-weight: normal;
  font-family: sans-serif;
  overflow: hidden;
  text-overflow: ellipsis;
  user-select: none;
  -moz-user-select: none;">
</div>
`;

const loadingTemplate = html`
<div style="
  font-weight: normal;
  font-family: sans-serif;
  user-select: none;
  -moz-user-select: none;
  text-align: center;
  font-size: 1rem;
  height: 3rem;
  line-height: 3rem;
  white-space: nowrap;
  overflow: hidden;
  position: relative;
  text-overflow: ellipsis;
  color: white;
  background: linear-gradient(
    90deg,
    #ef4c4c,
    #ef7b4a,
    #d0d000,
    #5cb85b,
    #20aca0,
    #6091ef,
    #9362ef,
    #e373d0,
    #9362ef,
    #6091ef,
    #20aca0,
    #5cb85b,
    #d0d000,
    #ef7b4a,
    #ef4c4c
  );
  background-size: 1000% 100%;
  animation: ropi-content-element-loading 32s linear infinite;">
</div>
`;

const errorTemplate = html`
<div style="background-color: #fcc;
  text-align: center;
  color: red;
  font-size: 1rem;
  padding: 1rem;
  font-weight: normal;
  font-family: sans-serif;
  user-select: none;
  -moz-user-select: none;">
</div>
`;

export default class RopiContentElement extends RopiHTMLElement {

  constructor() {
    super();

    this._uuid = UUID.v4();
    this._creationTimestamp = Date.now();
    this._httpClient = new HttpClient();
    this._canvas = null;
    this._configurationChanged = false;
    this._initialConfiguration = null;
    this._contents = {};

    this._configurationChangeHandler = (event) => {
      this._configurationChanged = true;

      this._canvas.contentEditor.dispatchEvent(new CustomEvent('contentdatachange', {
        bubbles: false
      }));

      this.renderElement();
    };

    this._editstartHandler = (event) => {
      if (event.target.hasAttribute('data-ropi-content-editable-empty')) {
        event.target.innerHTML = '&nbsp;';
        event.target.style.minHeight = event.target.offsetHeight + 'px';
        event.target.textContent = '';
      }
    };

    this._editendHandler = (event) => {
      if (event.detail.changed) {
        this._canvas.contentEditor.dispatchEvent(new CustomEvent('contentdatachange', {
          bubbles: false
        }));
      }

      event.target.style.minHeight = '';
      this._parseContentsFromRenderedElement(this.renderedElement);
    };
  }

  duplicate() {
    let newRenderedElement;

    if (this._lastSuccessfullyRenderedElement) {
      newRenderedElement = this._lastSuccessfullyRenderedElement.cloneNode(true);
    } else {
      newRenderedElement = this.renderedElement.cloneNode(true);
    }

    newRenderedElement.removeAttribute('data-ropi-content-element-uuid');
    newRenderedElement.removeAttribute('data-ropi-content-element-creation-timestamp');
    delete newRenderedElement.ropiContentElement;

    DOMUtil.insertAfter(this.renderedElement, newRenderedElement);

    let contentElements = newRenderedElement.querySelectorAll('[data-ropi-content-element]');
    for (let contentElement of contentElements) {
      contentElement.removeAttribute('data-ropi-content-element-uuid');
      contentElement.removeAttribute('data-ropi-content-element-creation-timestamp');
      delete contentElement.ropiContentElement;
    }

    this._renderedElement.dispatchEvent(new CustomEvent('contentelementduplicate', {
      bubbles: true,
      detail: {
        newRenderedElement: newRenderedElement
      }
    }));
  }

  initializeFromRenderedElement(renderedElement, dispatchEvents) {
    this._setRenderedElement(renderedElement, dispatchEvents, true);
    this._parseAttributesFromRenderedElement(renderedElement);
    this._parseConfigurationAttributeFromRenderedElement(renderedElement);
    this._parseContentsFromRenderedElement(renderedElement);
  }

  _parseAttributesFromRenderedElement(renderedElement) {
    let uuid = renderedElement.getAttribute('data-ropi-content-element-uuid');
    if (uuid) {
      this._uuid = uuid;
    }

    let creationTimestamp = renderedElement.getAttribute('data-ropi-content-element-creation-timestamp');
    if (creationTimestamp) {
      this._creationTimestamp = creationTimestamp;
    }
  }

  _parseConfigurationAttributeFromRenderedElement(renderedElement) {
    if (this._initialConfiguration) {
      return;
    }

    let configurationJson = renderedElement.getAttribute(
      'data-ropi-content-element-configuration'
    );

    if (!configurationJson) {
      return;
    }

    try {
      this._initialConfiguration = JSON.parse(configurationJson);
    } catch (error) {
      Logger.logError(
        'Failed to parse configuration attribute from rendered element: ',
        renderedElement,
        'The error was: ',
        error
      );
    }
  }

  _parseContentsFromRenderedElement(renderedElement) {
    let editableElements = this._queryEditableElementsFromRenderedElement(
      renderedElement
    );

    this._contents = {};

    let autoKey = 0;
    for (let editableElement of editableElements) {
      let key = editableElement.getAttribute('data-ropi-content-editable');
      if (!key) {
        key = autoKey.toString();
        autoKey++;
      }

      if (editableElement.textContent.trim() === '' || editableElement.textContent.trim() === TranslateElement.snippets.doubleClickTapToEdit) {
        editableElement.textContent = TranslateElement.snippets.doubleClickTapToEdit;
        editableElement.setAttribute('data-ropi-content-editable-empty', '');
        this._contents[key] = '';
      } else {
        editableElement.removeAttribute('data-ropi-content-editable-empty');
        this._contents[key] = editableElement.innerHTML;
      }
    }
  }

  _queryEditableElementsFromRenderedElement(renderedElement) {
    let editables = [];

    if (renderedElement.matches('[data-ropi-content-editable]')) {
      editables.push(renderedElement);
    }

    for (let child of renderedElement.children) {
      if (child.matches('[data-ropi-content-element]')) {
        continue;
      }

      if (child.matches('[data-ropi-content-editable]')) {
        editables.push(child);
        continue;
      }

      let childEditables = this._queryEditableElementsFromRenderedElement(child);
      for (let childEditable of childEditables) {
        editables.push(childEditable);
      }
    }

    return editables;
  }

  get group() {
    return (this.getAttribute('group') || '').trim();
  }

  get name() {
    return (this.getAttribute('name') || '').trim();
  }

  get icon() {
    return (this.getAttribute('icon') || 'drag_handle').trim();
  }

  get color() {
    return (this.getAttribute('color') || '').trim();
  }

  get type() {
    let type = (this.getAttribute('type') || '').trim();
    if (!type) {
      type = this.name.toLowerCase();
    }

    return type;
  }

  get contents() {
    return this._contents;
  }

  get uuid() {
    return String(this._uuid);
  }

  get creationTimestamp() {
    return String(this._creationTimestamp);
  }

  get languageSpecificSettings() {
    return this.hasAttribute('languagespecificsettings')
           ? this.getAttribute('languagespecificsettings').split(',').map((item) => {
               return item.trim();
             })
           : [];
  }

  get configuration() {
    if (!this._configurationElement) {
      this._renderConfigurationElement();
    }

    if (!this._configurationElement) {
      return {};
    }

    if (!this._configurationChanged && TypeUtil.isObject(this._initialConfiguration)) {
      return this._initialConfiguration;
    }

    return (new InputMapper(this._configurationElement)).values;
  }

  get configurationElement() {
    if (!this._configurationElement) {
      this._renderConfigurationElement();
    }

    return this._configurationElement;
  }

  set canvas(canvas) {
    this._canvas = canvas;
  }

  get canvas() {
    return this._canvas;
  }

  mapConfigurationToElement() {
    (new InputMapper(this._configurationElement)).values = this.configuration;
  }

  _renderConfigurationElement() {
    this._configurationElement = document.createElement('div');

    for (let child of this.children) {
      let node = this._cloneNode(child);
      if (node) {
        this._configurationElement.appendChild(node);
      }
    }

    if (this._configurationElement.children.length > 0) {
      // We add the configuration element to DOM and remove it again,
      // to force the browser to initialize webcomponents within the configuration element
      this.ownerDocument.body.appendChild(this._configurationElement);
      this.ownerDocument.body.removeChild(this._configurationElement);

      this._configurationElement.addEventListener(
        'change',
        this._configurationChangeHandler
      );
    }
  }

  get renderedElement() {
    if (!this._renderedElement) {
      this.renderElement();
    }

    return this._renderedElement;
  }

  _setRenderedElement(element, dispatchEvents, success) {
    if (!element) {
      element = this._createFallbackElement();
    }

    if (this._renderedElement && this._renderedElement.parentNode) {
      // Replace rendered element if already in DOM
      this._renderedElement.parentNode.replaceChild(
        element,
        this._renderedElement
      );
    }

    this._renderedElement = element;
    this._renderedElement.setAttribute('data-ropi-content-element', this.type);

    this._renderedElement.ropiContentElement = this;

    this._renderedElement.addEventListener(
      'editstart',
      this._editstartHandler
    );

    this._renderedElement.addEventListener(
      'editend',
      this._editendHandler
    );

    if (success) {
      this._parseContentsFromRenderedElement(this._renderedElement);
      this._parseConfigurationAttributeFromRenderedElement(this._renderedElement);
      this._lastSuccessfullyRenderedElement = this._renderedElement;
      this._executeJs(this._renderedElement);
    }

    if (dispatchEvents) {
      this._renderedElement.dispatchEvent(new CustomEvent('contentelementrender', {
        detail: {
          contentElement: this
        },
        bubbles: true,
        composed: true
      }));
    }
  }

  _executeJs(renderedElement) {
    let scripts = renderedElement.getElementsByTagName('script');
    for (let i = 0; i < scripts.length; ++i) {
      var script = scripts[i];

      var newScript = document.createElement('script');
      newScript.innerHTML = script.innerHTML;

      script.parentNode.insertBefore(newScript, script);
      script.parentNode.removeChild(script);
    }
  }

  renderElement(presetStructure) {
    let element;

    if (this.hasAttribute('src')) {
      element = this._createLoadingElement();
      this._renderRemotely(presetStructure);
    } else {
      element = this._createFallbackElement();
    }

    this._setRenderedElement(element, true);
  }

  _renderRemotely(presetStructure) {
    if (this._pendingRequest) {
      this._httpClient.abort(this._pendingRequest);
    }

    let httpRequest = this._buildRenderRequest(presetStructure);

    this._canvas.contentEditor.dispatchEvent(new CustomEvent('beforerenderrequest', {
      detail: {
        request: httpRequest,
        contentElement: this
      },
      bubbles: true,
      composed: true
    }));

    this._pendingRequest = httpRequest;

    this._httpClient
      .send(httpRequest)
      .then((httpResponse) => {
        let parentNode = this._renderedElement.parentNode;
        if (!parentNode) {
          return;
        }

        let template = document.createElement('template');
        template.innerHTML = httpResponse.getBody();

        let node = this._canvas.workingDocument.importNode(template.content.firstElementChild, true);

        this._setRenderedElement(node, true, true);
        delete this._pendingRequest;
      }).catch((error) => {
        let errorText = '[' + this.name + '] ' + error.toString();

        if (error instanceof HttpError) {
          errorText += ' (' + error.getHttpResponse().getStatusCode() + ')';
        }

        this._setRenderedElement(
          this._createErrorElement(errorText),
          true
        );
      });
  }

  _buildRenderRequest(presetStructure) {
    let httpRequest = (new HttpRequest(this._buildRenderUri()))
                      .setMethod(HttpRequest.METHOD_POST)
                      .setHeaders({
                        'Content-Type': 'application/json',
                        'X-Ropi-Content-Element': this.type.replace(/[\r\n]/g, '')
                      });

    httpRequest.setBody(JSON.stringify(this._buildRenderPayload(presetStructure)));

    return httpRequest;
  }

  _buildRenderUri() {
    let renderUri = this.getAttribute('src');
    if (renderUri.indexOf('https://') === 0 || renderUri.indexOf('http://') === 0) {
        return renderUri;
    }

    return renderUri;
  }

  _buildRenderPayload(presetStructure) {
    if (presetStructure) {
      let parsedStructure = JSON.parse(presetStructure);
      this._generateMetaDataForPresetStructure(parsedStructure)

      return {
        data: parsedStructure
      };
    }

    return {
      data: this._lastSuccessfullyRenderedElement
            ? DataParser.parseFromElement(this._lastSuccessfullyRenderedElement)
            : DataParser.buildDataFromContentElement(this)
    };
  }

  _generateMetaDataForPresetStructure(parsedStructure) {
    parsedStructure.meta.uuid = this._uuid;
    parsedStructure.meta.creationTimestamp = this.creationTimestamp;

    let processChildElements = (element) => {
      for (let childElement of element.children) {
        if (childElement.type === "element") {
          childElement.meta.uuid = UUID.v4();
          childElement.meta.creationTimestamp = Date.now();
        }

        processChildElements(childElement);
      }
    };

    processChildElements(parsedStructure);
  }

  _cloneNode(node) {
    if (!node) {
      return null;
    }

    if (node instanceof HTMLTemplateElement) {
      return node.content.cloneNode(true);
    }

    if (node.cloneNode) {
      return node.cloneNode(true);
    }

    return null;
  }

  _createLoadingElement() {
    let loadingElement = loadingTemplate
                          .cloneNode(true)
                          .content
                          .firstElementChild;

    loadingElement.textContent = 'Loading ' + this.name + '...';

    if (this._lastSuccessfullyRenderedElement) {
      let height = this._lastSuccessfullyRenderedElement.offsetHeight;
      if (height) {
        this._lastKnownHeight = height;
      }
    }

    if (this._lastKnownHeight) {
      loadingElement.style.height = this._lastKnownHeight + 'px';
      loadingElement.style.lineHeight = this._lastKnownHeight + 'px';
    }

    return loadingElement;
  }

  _createErrorElement(errorText) {
    let errorElement = errorTemplate
                        .cloneNode(true)
                        .content
                        .firstElementChild;

    errorElement.textContent = errorText;

    return errorElement;
  }

  _createFallbackElement() {
    let fallbackElement = fallbackTemplate
                        .cloneNode(true)
                        .content
                        .firstElementChild;

    fallbackElement.innerHTML = this.name;

    return fallbackElement;
  }
}

RopiContentElement._template = html`
<slot></slot>
`;

customElements.define('ropi-content-element', RopiContentElement);

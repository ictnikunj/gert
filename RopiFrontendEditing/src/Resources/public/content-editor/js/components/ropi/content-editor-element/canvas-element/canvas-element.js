import RopiHTMLElement from '../../html-element/html-element.js?v=1637255330';
import html from '../../html-tag/html-tag.js?v=1637255330';

import StringUtil from '../../string-util/string-util.js?v=1637255330';
import TypeUtil from '../../type-util/type-util.js?v=1637255330';
import Logger from '../../logger/logger.js?v=1637255330';
import GestureEvents from '../../gesture-events/gesture-events.js?v=1637255330';
import Editable from '../../editable/editable.js?v=1637255330';
import Draggable from '../../draggable/draggable.js?v=1637255330';

import '../../button-element/button-element.js?v=1637255330';
import '../../error-element/error-element.js?v=1637255330';
import DOMUtil from "../../dom-util/dom-util.js?v=1637255330";
import DataParser from '../data-parser/data-parser.js?v=1637255330';

const styleTemplate = html`
<style>
@keyframes ropi-content-element-loading {
	0% {
		background-position: 0% 50%
	}
	100% {
		background-position: 1000% 50%
	}
}

.ropi-content-editor-dragging [data-ropi-content-area],
.ropi-content-editor-editmode [data-ropi-content-area] {
  min-height: 2.5rem;
  position: relative;
  margin-top: 2px;
  margin-bottom: 2px;
  padding-top: 2px;
  padding-bottom: 2px;
}

.ropi-content-editor-dragging [data-ropi-content-area] [data-ropi-content-area],
.ropi-content-editor-editmode [data-ropi-content-area] [data-ropi-content-area] {
  margin-top: 0;
  margin-bottom: 0;
  padding-top: 0;
  padding-bottom: 0;
}

.ropi-content-editor-editmode [data-ropi-content-area] {
  outline: dashed 1px rgba(120,120,120,0.5);
  outline-offset: 0;
  background: rgba(127,127,127,0.05);
}
/*
.ropi-content-editor-editmode:not(.ropi-content-editor-dragging) [data-ropi-content-area] [data-ropi-content-area] {
	outline: none;
	box-shadow: none;
}
*/
.ropi-content-editor-previewmode:not(.ropi-content-editor-dragging) :not([contenteditable="true"])[data-ropi-content-editable-empty] {
  font-size: 0;
}

.ropi-content-editor-editmode :not([contenteditable="true"])[data-ropi-content-editable-empty],
.ropi-content-editor-dragging [data-ropi-content-editable-empty] {
  font-style: italic;
  font-weight: normal;
  opacity: 0.5;
}

.ropi-content-editor-editmode [data-ropi-content-element],
.ropi-content-editor-dragging [data-ropi-content-element] {
  cursor: default;
  user-select: none;
  -moz-user-select: none;
  position: relative;
}

[contenteditable="true"] {
  cursor: text;
}

.ropi-content-editor-dragging [data-ropi-content-area]:not(.ropi-content-area-disallowed) {
  outline-style: dashed !important;
  outline-width: 1px !important;
  outline-offset: 0 !important;
}

.ropi-content-editor-dragging [data-ropi-content-area].ropi-content-area-disallowed {
  outline: dashed 1px rgba(120,120,120,0.15);
}

.ropi-content-editor-dragging [data-ropi-content-area].ropi-content-area-dragover {
  outline-width: 2px !important;
  outline-offset: 0 !important;
  z-index: 1;
}

.ropi-content-editor-dragging [data-ropi-content-area].ropi-content-area-dragover::after {
    display: block;
}

/*
[data-ropi-content-element]:focus,
[data-ropi-content-editable]:focus,
*/
.ropi-content-editor-editmode [data-ropi-content-element].ropi-content-element-mouseover {
  outline-style: solid !important;
  outline-width: 2px !important;
  outline-offset: 0 !important;
  position: relative;
  z-index: 1;
}

.ropi-content-editor-dragging [data-ropi-content-area]::after,
.ropi-content-editor-editmode .ropi-content-element-layer {
	content: " ";
	position: absolute;
	z-index: 2147483647;
	width: 100%;
	height: 100%;
	left: 0;
	top: 0;
	background-color: #6091ef;
	opacity: 0;
	pointer-events: none;
}

.ropi-content-editor-dragging [data-ropi-content-area]::after {
    display: none;
	opacity: 0.25;
	z-index: 2147483646;
}

.ropi-content-editor-editmode [data-ropi-content-element].ropi-content-element-mouseover > .ropi-content-element-layer {
	opacity: 0.1;
}

.ropi-content-editor-editmode [data-ropi-content-element] a[href] {
	cursor: default !important;
}

.ropi-content-editor-dragging [data-ropi-content-element],
.ropi-content-editor-dragging [data-ropi-content-element].ropi-content-element-mouseover,
.ropi-content-editor-dragging [data-ropi-content-element]:focus,
.ropi-content-editor-dragging [data-ropi-content-editable]:focus {
  outline: none !important;
}

.ropi-content-editor-editmode [data-ropi-content-element].ropi-content-element-active {
    outline-width: 3px !important;
    outline-style: solid !important;
    outline-offset: 0 !important;
    position: relative;
	z-index: 1;
    box-shadow: 0px 0px 12px 0px rgba(50,50,50,0.9);
}

.ropi-content-editor-editmode [data-ropi-content-editable][contenteditable="true"] {
	outline: none;
}
/*
.ropi-content-editor-editmode [data-ropi-content-element].ropi-content-element-active::after {
	background-color: blue;
}
*/
[data-ropi-content-element].ropi-content-element-moving {
  opacity: 0.33 !important;
  outline: none !important;
}

/* Spacing while dragging a content element or in edit mode */
/* ensure each content element has space before and after to allow user to drop a content element between two existing content elements while dragging */

.ropi-content-editor-editmode [data-ropi-content-area] > :not(.ropi-content-editor-drop-marker),
.ropi-content-editor-dragging [data-ropi-content-area] > :not(.ropi-content-editor-drop-marker) {
    margin-top: 4px;
    margin-bottom: 4px;
}
</style>
`;

const dropMarkerTemplate = html`
<div
	class="ropi-content-editor-drop-marker"
	style="
	pointer-events: none !important;
	padding: 0 !important;
	margin: 0 !important;
	height: 0 !important;
	position: relative !important;
	z-index: 2147483647 !important;
	width: 100% !important;
	">
	<div style="position: absolute;
	    left: 0;
	    top: -1px;
	    height: 6px;
	    width: 100%;
        background-color: #6091ef;
        box-shadow: 0px 0px 8px 0 rgba(50,50,50,0.9)"></div>
</div>
`;

export default class RopiContentEditorCanvasElement extends RopiHTMLElement {

    static get observedAttributes() {
        return ['dragging', 'editmode'];
    }

    createDropMarker(color) {
        let dropMarker = dropMarkerTemplate.content.cloneNode(true).firstElementChild;
        if (color) {
            dropMarker.firstElementChild.style.backgroundColor = color;
            dropMarker.firstElementChild.style.outlineColor = color;
        }
        return dropMarker;
    }

    constructor() {
        super();

        this._history = [];
        this._contentEditor = null;
        this._errorBackButton = this.shadowRoot.getElementById('error-back-button');

        this._loadHandler = (e) => {
            if (this.hasCspError) {
                this.setAttribute('error', '');

                if (!this._ghostUrl) {
                    this._history.push(this.iframe.src);
                }
            } else {
                this.removeAttribute('error');

                if (this._ghostUrl !== this.workingDocument.location.href) {
                    this._history.push(this.workingDocument.location.href);
                }
            }

            this._ghostUrl = null;
            this._documentVersions = null;

            this.parseContentElements();

            GestureEvents.enableDoubleClick(this.workingDocument);

            // Assign event listeners
            this.workingDocument.addEventListener('contentelementrender', this._contentElementRenderHandler);
            this.workingDocument.addEventListener('contentelementduplicate', this._contentElementDuplicateHandler);
            this.workingDocument.addEventListener('click', this._workingDocumentClickHandler);
            this.workingDocument.addEventListener('doubleclick', this._workingDocumentClickHandler);

            // Inject CSS to iframe
            this.workingDocument.body.appendChild(
                styleTemplate.content.cloneNode(true).firstElementChild
            );

            this.workingDocument.body.classList.add('ropi-content-editor');

            this._updateAreas();

            this.dispatchEvent(new CustomEvent('load'));

            this._updateAreaColors();

            this.workingDocument.dispatchEvent(new CustomEvent('ropiContentEditorDocumentInitialized'));
        };

        this._resizeHandler = () => {
            this.iframe.setAttribute('width', this.offsetWidth);
            this.iframe.setAttribute('height', this.offsetHeight);
        };

        this._workingDocumentClickHandler = (event) => {
            if (!this.hasAttribute('editmode') || event.preventRopiContentEditorSettingsPanel) {
                this._setActiveContentElementByRenderedElement(null, true);
                return;
            }

            event.stopPropagation();

            if (this.hasAttribute('dragging')) {
                return;
            }

            let renderedElement;

            if (event.target.closest) {
                renderedElement = event.target.closest('[data-ropi-content-element]');
            }

            if (renderedElement) {
                if (renderedElement.isContentEditable) {
                    return;
                }

                let editingElement = renderedElement.querySelector('[data-ropi-content-editable][contentEditable="true"]');
                if (editingElement) {
                    return;
                }
            }

            this._setActiveContentElementByRenderedElement(renderedElement, true);
        };

        this._contentElementMouseoverHandler = (event) => {
            if (!this.hasAttribute('editmode')) {
                return;
            }

            let renderedContentElement = event.target.closest('[data-ropi-content-element]');
            if (renderedContentElement === event.currentTarget) {
                if (this._lastMouseOverElement) {
                    if (this._lastMouseOverElement !== event.currentTarget) {
                        this._lastMouseOverElement.classList.remove('ropi-content-element-mouseover');
                    }
                }

                if (renderedContentElement.querySelector('[contenteditable="true"]')){
                    return;
                }

                this._lastMouseOverElement = event.currentTarget;

                if (renderedContentElement.classList.contains('ropi-content-element-mouseover')) {
                    return;
                }

                renderedContentElement.classList.add('ropi-content-element-mouseover');
            }
        };

        this._contentElementMouseleaveHandler = (event) => {
            if (!this.hasAttribute('editmode')) {
                return;
            }

            event.currentTarget.classList.remove('ropi-content-element-mouseover');
        };

        this._contentElementRenderHandler = (event) => {
            this.parseContentElements();

            if (this.activeContentElement === event.target.ropiContentElement) {
                // Reset active class on re-render active element
                // (could happen if user changes content element configuration)
                this.activeContentElement.renderedElement.classList.add(
                    'ropi-content-element-active'
                );
            }

            this.dispatchEvent(new CustomEvent('change'));

            this._updateAreaColors();
        };

        this._contentElementDuplicateHandler = (event) => {
            this.parseContentElements();

            this._setActiveContentElementByRenderedElement(null, true);

            this.dispatchEvent(new CustomEvent('change'));

            this._setActiveContentElementByRenderedElement(event.detail.newRenderedElement, true);
            requestAnimationFrame(() => {
                event.detail.newRenderedElement.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });

                event.detail.newRenderedElement.ropiContentElement.renderElement();
            });
        };

        this._contentElementAnchorClickHandler = (event) => {
            if (this.hasAttribute('editmode')) {
                event.preventDefault();
            }
        };

        this._editableElementClickHandler = (event) => {
            if (this.hasAttribute('editmode')) {
                if (event.currentTarget.getAttribute('contenteditable') === 'true') {
                    if (this._lastMouseOverElement) {
                        this._lastMouseOverElement.classList.remove('ropi-content-element-mouseover');
                    }
                } else {
                    event.preventDefault();
                }
            }
        };

        this._linkClickHandler = (event) => {
            if (this.hasAttribute('editmode') || event.currentTarget.getAttribute('contenteditable') === 'true') {
                return;
            }

            let link = event.target.closest('a[href]');
            if (!link) {
                return;
            }

            let href = link.getAttribute('href').trim();
            if (this._isCurrentDomain(href)) {
                return;
            }

            event.preventDefault();

            window.open(href, '_blank');
        };

        this._errorBackButtonClickHandler = () => {
            this.historyBack();
        };
    }

    attributeChangedCallback(name, valueBefore, value) {
        if (value === valueBefore) {
            return;
        }

        if (name === 'dragging') {
            var documentElement = this.workingDocument.documentElement;
            var maxScrollPosition = documentElement.scrollHeight - documentElement.clientHeight;
            var scrollFactor = 1 / maxScrollPosition * documentElement.scrollTop;

            if (this.hasAttribute('dragging')) {
                this.workingDocument.body.classList.add('ropi-content-editor-dragging');
                this._updateAreas();
            } else {
                this.workingDocument.body.classList.remove('ropi-content-editor-dragging');
            }

            if (maxScrollPosition > 0) {
                // Set percentual scroll position, because in dragging mode the elements are usally larger
                maxScrollPosition = documentElement.scrollHeight - documentElement.clientHeight;
                documentElement.scrollTop = maxScrollPosition * scrollFactor;
            }
        } else if (name === 'editmode') {
            this._updateElements();
            this._updateAreas();

            this.workingDocument.dispatchEvent(new CustomEvent('ropiContentEditorEditModeChanged'));
        }
    }

    connectedCallback() {
        this.iframe.addEventListener('load', this._loadHandler);
        window.addEventListener('resize', this._resizeHandler);
        this._errorBackButton.addEventListener('click', this._errorBackButtonClickHandler);
    }

    disconnectedCallback() {
        this.iframe.removeEventListener('load', this._loadHandler);
        window.removeEventListener('resize', this._resizeHandler);
        this._errorBackButton.removeEventListener('click', this._errorBackButtonClickHandler);
    }

    get iframe() {
        return this.shadowRoot.querySelector('iframe');
    }

    get hasCspError() {
        try {
            if ((this.iframe.contentDocument || this.iframe.contentWindow.document) && this.workingDocument.location.origin === location.origin) {
                return false;
            }
        } catch (e) {
            // Silent fail
        }

        return true;
    }

    get workingDocument() {
        try {
            return this.iframe.contentDocument || this.iframe.contentWindow.document;
        } catch (e) {
            // Silet fail
        }

        return document.implementation.createHTMLDocument('Error');
    }

    get contentAreaElements() {
        return this.workingDocument.querySelectorAll('[data-ropi-content-area]');
    }

    get contentElements() {
        return this.workingDocument.querySelectorAll(
            '[data-ropi-content-element]'
        );
    }

    get src() {
        return this.workingDocument.location.href || this.iframe.src;
    }

    set src(src) {
        this.iframe.src = src;
    }

    set activeContentElement(activeContentElement) {
        this._setActiveContentElementByRenderedElement(
            activeContentElement instanceof Element
                ? activeContentElement.renderedElement
                : null
        );
    }

    get activeContentElement() {
        return this._activeContentElement;
    }

    set contentEditor(contentEditor) {
        this._contentEditor = contentEditor;
    }

    get contentEditor() {
        return this._contentEditor;
    }

    isContentElementTypeAllowedForArea(contentElementType, contentAreaElement) {
        let parentMoving = contentAreaElement.closest('.ropi-content-element-moving');
        if (parentMoving) {
            return false;
        }

        if (!contentElementType) {
            return false;
        }

        let disallowedElementTypes = StringUtil.parseList(
            contentAreaElement.getAttribute('data-ropi-content-area-disallowed')
        );

        if (disallowedElementTypes.length > 0
            && disallowedElementTypes.includes(contentElementType)) {
            return false;
        }

        let allowedElementTypes = StringUtil.parseList(
            contentAreaElement.getAttribute('data-ropi-content-area-allowed')
        );

        if (allowedElementTypes.length > 0
            && !allowedElementTypes.includes(contentElementType)) {
            return false;
        }

        return true;
    }

    _setActiveContentElementByRenderedElement(renderedElement, dispatchEvents) {
        let contentElement = renderedElement
            ? renderedElement.ropiContentElement
            : null;

        if (this._activeContentElement) {
            if (contentElement === this._activeContentElement) {
                contentElement = null;
            }

            this._activeContentElement.renderedElement.classList.remove(
                'ropi-content-element-active'
            );

            this._activeContentElement.blur();

            this._activeContentElement = null;
        }

        if (contentElement) {
            contentElement.renderedElement.classList.add('ropi-content-element-active');
            this._activeContentElement = contentElement;
        }

        if (dispatchEvents) {
            this.dispatchEvent(new CustomEvent('selectelement', {
                detail: {
                    contentElement: this._activeContentElement
                }
            }));
        }
    }

    get data() {
        return DataParser.parseFromDocument(this.workingDocument);
    }

    get documentContext() {
        return DataParser.parseDocumentContext(this.workingDocument, true);
    }

    get documentVersions() {
        if (TypeUtil.isArray(this._documentVersions)) {
            return this._documentVersions;
        }

        if (this.workingDocument.body.hasAttribute('data-ropi-document-versions')) {
            try {
                this._documentVersions = JSON.parse(this.workingDocument.body.getAttribute('data-ropi-document-versions'));
            } catch (e) {
                Logger.logWarning(
                    'Failed to parse document versions:',
                    e
                );
            }
        }

        if (!TypeUtil.isArray(this._documentVersions)) {
            this._documentVersions = [];
        }

        for (let version of this._documentVersions) {
            let timestamp = parseInt(version.time, 10);

            if (String(version.time).length <= 10) {
                timestamp *= 1000;
            }

            version.formattedTime = (new Date(timestamp)).toLocaleString();
        }

        return this._documentVersions;
    }

    parseContentElements() {
        this._updateElements();

        this.contentElements.forEach((contentElement) => {
            GestureEvents.enableDoubleClick(contentElement);

            //contentElement.setAttribute('tabindex', '0');
            contentElement.removeEventListener('mouseover', this._contentElementMouseoverHandler);
            contentElement.addEventListener('mouseover', this._contentElementMouseoverHandler);
            contentElement.removeEventListener('mouseleave', this._contentElementMouseleaveHandler);
            contentElement.addEventListener('mouseleave', this._contentElementMouseleaveHandler);

            let hrefElements = contentElement.querySelectorAll('a[href]');
            hrefElements.forEach((hrefElement) => {
                hrefElement.removeEventListener('click', this._contentElementAnchorClickHandler);
                hrefElement.addEventListener('click', this._contentElementAnchorClickHandler);
            });
        });
    }

    _isCurrentDomain(uri) {
        uri = uri.trim();

        if (uri.indexOf('://') === -1) {
            return true;
        }

        if (uri.indexOf(location.href) === 0) {
            return true;
        }

        return false;
    }

    _updateElements() {
        if (this.hasAttribute('editmode')) {
            this._enableEditableElements();
            this._enableDraggableElements();
        } else {
            this._disableEditableElements();
            this._disableDraggableElements();
            this._updateLinks();
        }
    }

    _updateAreaColors() {
        let contentElements = this.workingDocument.querySelectorAll('[data-ropi-content-element]');
        for (let contentElement of contentElements) {
            let color = '#6091ef';
            if (contentElement.ropiContentElement && contentElement.ropiContentElement.color) {
                color = contentElement.ropiContentElement.color;
            }

            contentElement.style.outlineColor = color;

            let areaElements = DOMUtil.closestChildren(contentElement, '[data-ropi-content-area]');
            for (let areaElement of areaElements) {
                areaElement.style.outlineColor = color;
            }

            let layer = contentElement.querySelector(':scope > .ropi-content-element-layer');
            if (!layer) {
                layer = document.createElement('div');
                layer.classList.add('ropi-content-element-layer');
                contentElement.appendChild(layer);
            }

            layer.style.backgroundColor = color;
        }
    }

    _updateAreas() {
        if (this.hasAttribute('editmode')) {
            this.workingDocument.body.classList.add('ropi-content-editor-editmode');
            this.workingDocument.body.classList.remove('ropi-content-editor-previewmode');
        } else {
            this.workingDocument.body.classList.remove('ropi-content-editor-editmode');
            this.workingDocument.body.classList.add('ropi-content-editor-previewmode');
        }

        if (this.hasAttribute('dragging')) {
            let contentAreas = this.workingDocument.querySelectorAll('[data-ropi-content-area]');

            for (let contentArea of contentAreas) {
                let allowed = this.isContentElementTypeAllowedForArea(
                    this.getAttribute('dragging'), contentArea
                );

                if (allowed) {
                    contentArea.classList.remove('ropi-content-area-disallowed');
                } else {
                    contentArea.classList.add('ropi-content-area-disallowed');
                }
            }
        }
    }

    _updateLinks() {
        let links = this.workingDocument.querySelectorAll('[data-ropi-content-editable][href], [data-ropi-content-editable] [href]');
        for (let link of links) {
            link.removeEventListener('click', this._linkClickHandler);
            link.addEventListener('click', this._linkClickHandler);
        }
    }

    _enableEditableElements() {
        let editableElements = this.workingDocument.querySelectorAll('[data-ropi-content-editable]');
        for (let editableElement of editableElements) {
            (new Editable(editableElement)).enable();

            editableElement.removeEventListener('click', this._editableElementClickHandler, true);
            editableElement.addEventListener('click', this._editableElementClickHandler, true);
        }
    }

    _disableEditableElements() {
        let editableElements = this.workingDocument.querySelectorAll('[data-ropi-content-editable]');
        for (let editableElement of editableElements) {
            if (editableElement.ropiEditable) {
                editableElement.ropiEditable.destroy();
            }

            editableElement.removeEventListener('click', this._editableElementClickHandler, true);
        }
    }

    _enableDraggableElements() {
        this.contentElements.forEach((contentElement) => {
            let draggable = new Draggable(contentElement);
            draggable.enable();
        });
    }

    _disableDraggableElements() {
        this.contentElements.forEach((contentElement) => {
            if (contentElement.ropiDraggable) {
                contentElement.ropiDraggable.destroy();
            }
        });
    }

    historyBack() {
        if (this._history.length <= 1) {
            return;
        }

        this._history.pop();

        this._ghostUrl = this._history[this._history.length - 1];
        this.iframe.src = this._ghostUrl;
    }

    reload() {
        this._ghostUrl = this.src;
        this.iframe.src = this._ghostUrl;
    }

    removeContentElements() {
        this.contentElements.forEach((contentElement) => {
            if (contentElement.parentNode) {
                contentElement.parentNode.removeChild(contentElement);
            }
        });
    }
}

RopiContentEditorCanvasElement._template = html`
<style>
  :host {
    display: block;
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 100%;
    background-color: white;
    z-index: 0;
    outline: solid 0.0625rem var(--ropi-color-contrast-medium, grey);
    box-sizing: border-box;
    transition: opacity var(--ropi-animation-duration, 301ms) ease;
  }

	iframe {
    width: 100%;
    height: 100%;
    border: none;
    position: absolute;
    left: 0;
    top: 0;
  }

  :host([dragging]) > iframe {
    pointer-events: none;
  }

	:host(:not([error])) #error {
		display: none;
	}

	#error-back-button {
		margin-top: 1rem;
	}
</style>
<iframe></iframe>
<ropi-error id="error">
	Sorry, the requested page can not be displayed due to content security policy.
	<ropi-button id="error-back-button">Go back</ropi-button>
</ropi-error>
`;

customElements.define('ropi-content-editor-canvas', RopiContentEditorCanvasElement);

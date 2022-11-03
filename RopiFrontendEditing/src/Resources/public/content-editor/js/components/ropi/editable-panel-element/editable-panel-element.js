import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

import StringUtil from '../string-util/string-util.js?v=1637255330';
import DOMUtil from '../dom-util/dom-util.js?v=1637255330';
import SelectionUtil from '../selection-util/selection-util.js?v=1637255330';
import TranslateElement from '../translate-element/translate-element.js?v=1637255330';
import DialogElement from '../dialog-element/dialog-element.js?v=1637255330';

import '../material-icon-element/material-icon-element.js?v=1637255330';
import '../touchable-element/touchable-element.js?v=1637255330';
import '../vertical-scroll-element/vertical-scroll-element.js?v=1637255330';
import '../textfield-element/textfield-element.js?v=1637255330';

export default class RopiEditablePanelElement extends RopiHTMLElement {

  constructor() {
    super();

    this._linkDialog = this.shadowRoot.getElementById('link-dialog');
    this._hrefAssistantDialog = this.shadowRoot.getElementById('href-assistant-dialog');
    this._hrefAssistantSlot = this.shadowRoot.querySelector('slot[name="href-assistant"]');
    this._hrefInput = this.shadowRoot.getElementById('href-input');
    this._targetInput = this.shadowRoot.getElementById('target-input');
    this._openHrefButton = this.shadowRoot.getElementById('open-href-button');
    this._hrefAssistantButton = this.shadowRoot.getElementById('href-assistant-button');

    this._formatButtons = this.shadowRoot.querySelectorAll('.tools > ropi-touchable');
    for (let button of this._formatButtons) {
      button.setAttribute('title', TranslateElement.translate(
        StringUtil.capitalize(
          button.id.replace(/([A-Z])/g, ' $1').toLowerCase()
        )
      ));
    }

    this._mousedownHandler = (event) => {
      let dialog = event.target.closest('ropi-dialog');
      if (dialog && dialog.id === 'link-dialog') {
        return;
      }

      event.preventDefault();
    };

    this._actionClickHandler = (event) => {
      this.dispatchEvent(new CustomEvent('action', {
        detail: {
          action: event.currentTarget.id,
          ok: event.currentTarget.id === 'ok',
          cancel: event.currentTarget.id === 'cancel'
        }
      }));
    };

    this._formatButtonClickHandler = (event) => {
      let htmlFor = this.htmlFor;
      if (!htmlFor) {
        return;
      }

      if (this.htmlFor.ownerDocument.activeElement !== this.htmlFor) {
        this.htmlFor.focus();
      }

      let command = event.currentTarget.id;

      if (command === 'createLink') {
        this._openLinkDialog();
      } else if (command === 'unlink') {
        this._unlink();
      } else {
        htmlFor.ownerDocument.execCommand(command);
      }

      // Clean up spans inserted by browser
      let spans = htmlFor.querySelectorAll('span');
      for (let span of spans) {
        DOMUtil.unwrap(span);
      }

      this._updateToolbar();
    };

    this._selectionchangeHandler = () => {
      this._updateToolbar();
    };

    this._openHrefButtonClickHandler = () => {
      if (this.hasAttribute('disabled') || this._hrefInput.value.trim() === '') {
        return;
      }

      let href = this._hrefInput.value.trim();

      let openhrefEvent = new CustomEvent('openhref', {
        detail: {
          href: href
        },
        cancelable: true,
        composed: true
      });

      this.dispatchEvent(openhrefEvent);

      if (openhrefEvent.defaultPrevented) {
        return;
      }

      window.open(href, '_blank');
    };

    this._hrefInputKeypressHandler = () => {
      if (this._hrefInput.value.trim() === '') {
        this._openHrefButton.setAttribute('disabled', '');
      } else {
        this._openHrefButton.removeAttribute('disabled');
      }
    };

    this._hrefAssistantButtonClickHandler = () => {
      if (this._hrefAssistantButton.hasAttribute('disabled')) {
        return;
      }

      this._linkDialog.removeAttribute('open');
      this._hrefAssistantDialog.setAttribute('open', '');

      this._hrefAssistantDialog.ondialogclose = (event) => {
        this.dispatchEvent(new CustomEvent('openhrefassistant', {
          detail: {
            input: this._hrefInput
          },
          composed: true
        }));

        this._linkDialog.setAttribute('open', '');
      };
    };

    this._hrefAssistantSlotChangeHandler = () => {
      this._updateHrefAssistant();
    };
  }

  connectedCallback() {
    for (let formatButton of this._formatButtons) {
      formatButton.addEventListener('click', this._formatButtonClickHandler);
    }

    this._addElementListeners();

    this.shadowRoot.addEventListener('mousedown', this._mousedownHandler);

    this.shadowRoot.getElementById('ok').addEventListener('click', this._actionClickHandler);
    this.shadowRoot.getElementById('cancel').addEventListener('click', this._actionClickHandler);

    this._hrefAssistantButton.addEventListener('click', this._hrefAssistantButtonClickHandler);
    this._openHrefButton.addEventListener('click', this._openHrefButtonClickHandler);
    this._hrefInput.addEventListener('keypress', this._hrefInputKeypressHandler);
    this._hrefInput.addEventListener('keyup', this._hrefInputKeypressHandler);
    this._hrefAssistantSlot.addEventListener('slotchange', this._hrefAssistantSlotChangeHandler);

    this._updateHrefAssistant();
  }

  _updateHrefAssistant() {
    if (this._hrefAssistantSlot.assignedElements().length === 0) {
      if (this._hrefAssistantDialog.hasAttribute('open')) {
        this._hrefAssistantDialog.removeAttribute('open');
      }

      this._hrefAssistantButton.style.display = 'none';
    } else {
      this._hrefAssistantButton.style.display = '';
    }
  }

  _openLinkDialog() {
    if (this.htmlFor.ropiEditable) {
      this.htmlFor.ropiEditable.preventCloseOnBlur = true;
    }

    let link = this._getLinkElementOfCurrentSelection();

    if (link) {
      this._hrefInput.value = (link.getAttribute('href') || '').trim();
      this._targetInput.value = (link.getAttribute('target') || '').trim();
      link.style._ropiBackgroundColor = link.style.backgroundColor;
      link.style._ropiColor = link.style.color;
      link.style.backgroundColor = 'grey';
      link.style.color = 'white';
    } else {
      this._hrefInput.value = '';
      this._targetInput.value = '';
    }

    if (this._hrefInput.value) {
      this._openHrefButton.removeAttribute('disabled');
    } else {
      this._openHrefButton.setAttribute('disabled', '');
    }

    let restoreSelection = SelectionUtil.saveSelection(this.htmlFor.ownerDocument.defaultView);

    this._linkDialog.setAttribute('open', '');
    this._hrefInput.focus();

    this._linkDialog.ondialogclose = (event) => {
      if (this._hrefAssistantDialog.hasAttribute('open')) {
        return;
      }

      if (this.htmlFor.ropiEditable) {
        this.htmlFor.ropiEditable.preventCloseOnBlur = false;
        this.htmlFor.focus();
      }

      if (link) {
        link.style.backgroundColor = link.style._ropiBackgroundColor;
        link.style.color = link.style._ropiColor;

        if (!link.getAttribute('style')) {
          link.removeAttribute('style');
        }
      }

      if (event.detail.action === DialogElement.ACTION_PRIMARY) {
        let href = this._hrefInput.value.trim();
        let target = this._targetInput.value.trim().toLowerCase();

        if (link) {
          if (href) {
            link.setAttribute('href', href);
          } else {
            DOMUtil.unwrap(link);
          }

          restoreSelection();
        } else {
          restoreSelection();
          this.htmlFor.ownerDocument.execCommand('createLink', false, href);
          link = this._getLinkElementOfCurrentSelection();
        }

        if (link) {
          if (target && target !== '_self') {
            link.setAttribute('target', target);
            link.setAttribute('rel', 'noopener');
          } else {
            link.removeAttribute('target');
            link.removeAttribute('rel');
          }
        }
      } else {
        restoreSelection();
      }
    };
  }

  _unlink() {
    let link = this._getLinkElementOfCurrentSelection();

    let restoreSelection = SelectionUtil.saveSelection(this.htmlFor.ownerDocument.defaultView);

    if (link) {
      this._selectNode(link);
    }

    this.htmlFor.ownerDocument.execCommand('unlink');

    restoreSelection();
  }

  _selectNode(node) {
    if (!node || !node.parentNode) return;

    let selection = this.htmlFor.ownerDocument.defaultView.getSelection();
    selection.removeAllRanges();

    let range = this.htmlFor.ownerDocument.createRange();
    range.selectNode(node);
    selection.addRange(range);

    this._updateToolbar();
  }

  _updateToolbar() {
    let htmlFor = this.htmlFor;
    if (!htmlFor) {
      return;
    }

    if (!htmlFor.isContentEditable) {
      return;
    }

    let touchables = this.shadowRoot.querySelectorAll('.tools > ropi-touchable');
    for (let touchable of touchables) {
      let command = touchable.id;
      if (command === 'unlink') {
        let link = this._getLinkElementOfCurrentSelection();
        if (link) {
          touchable.classList.add('active');
        } else {
          touchable.classList.remove('active');
        }

      /*} else if (command === 'redo' || command === 'undo') {
        let canApply = htmlFor.ownerDocument.queryCommandEnabled(command);
        if (canApply) {
          touchable.removeAttribute('disabled');
        } else {
          touchable.setAttribute('disabled', '');
        }*/

      } else {
        let hasFormat = htmlFor.ownerDocument.queryCommandState(command);
        if (hasFormat) {
          touchable.classList.add('active');
        } else {
          touchable.classList.remove('active');
        }
      }
    }
  }

  _getLinkElementOfCurrentSelection() {
    let htmlFor = this.htmlFor;
    let node = htmlFor.ownerDocument.defaultView.getSelection().anchorNode;

    while (node && node.nodeName !== 'A') {
      node = node.parentNode;

      if (node === htmlFor) {
        break;
      }
    }

    if (node && node.nodeName === 'A') {
      return node;
    }

    return null;
  }

  _addElementListeners() {
    let htmlFor = this.htmlFor;
    if (!htmlFor) {
      return;
    }

    htmlFor.ownerDocument.removeEventListener('selectionchange', this._selectionchangeHandler);
    htmlFor.ownerDocument.addEventListener('selectionchange', this._selectionchangeHandler);
  }

  _removeElementListeners() {
    let htmlFor = this.htmlFor;
    if (!htmlFor) {
      return;
    }

    htmlFor.removeEventListener('selectionchange', this._selectionchangeHandler);
  }

  set htmlFor(htmlFor) {
    this._removeElementListeners();

    this._htmlFor = htmlFor;
    this.shadowRoot.querySelector('ropi-vertical-scroll').scrollPosition = 0;

    this._addElementListeners();
  }

  get htmlFor() {
    let htmlFor = this._htmlFor ? this._htmlFor : this.getAttribute('for');
    if (!htmlFor) {
      return null;
    }

    if (htmlFor.parentNode) {
      return htmlFor;
    }

    if (typeof htmlFor === 'string' && this.parentNode) {
      htmlFor = this.ownerDocument.getElementById(htmlFor);
      return htmlFor;
    }

    return null;
  }

  get commands() {
    let commandElements = this.shadowRoot.querySelectorAll('.format-command');

    let commands = [];
    for (let commandElement of commandElements) {
      commands.push(commandElement.id);
    }

    return commands;
  }
}

RopiEditablePanelElement._template = html`
<style>
  :host {
    background-color: var(--ropi-color-base, black);
    display: block;
    width: 100%;
  }

  .tools {
    display: flex;
    flex-wrap: nowrap;
  }

  .tools ropi-touchable,
  .topbar ropi-touchable {
    width: 3rem;
    height: 3rem;
    min-width: 3rem;
    line-height: 3rem;
    text-align: center;
    vertical-align: middle;
  }

  ropi-touchable.active {
    color: var(--ropi-color-interactive, blue);
  }

  ropi-touchable[disabled] {
    opacity: 0.5;
  }

  .row {
    width: 100%;
    position: relative;
  }

  #ok {
    position: absolute;
    top: 0;
    right: 0;
  }

  .vline {
    height: 3rem;
    width: 0.0625rem;
    min-width: 0.0625rem;
    background-color: var(--ropi-color-material-50, darkgrey);
  }

  :host([hidebold]) #bold,
  :host([hideitalic]) #italic,
  :host([hideunderline]) #underline,
  :host([hidestrikethrough]) #strikeThrough,
  :host([hidecreatelink]) #createLink,
  :host([hideunlink]) #unlink,
  :host([hideinsertorderedlist]) #insertOrderedList,
  :host([hideinsertunorderedlist]) #insertUnorderedList,
  :host([hidejustifyleft]) #justifyLeft,
  :host([hidejustifycenter]) #justifyCenter,
  :host([hidejustifyright]) #justifyRight,
  :host([hidejustifyfull]) #justifyFull/*,
  :host([hideindent]) #indent,
  :host([hideoutdent]) #outdent*/ {
    display: none;
  }

  :host([hidebold][hideitalic][hideunderline][hidestrikethrough]) #vline-basic {
    display: none;
  }

  :host([hidecreatelink][hideunlink]) #vline-link {
    display: none;
  }

  :host([hideinsertorderedlist][hideinsertunorderedlist]) #vline-list {
    display: none;
  }

  :host([hidejustifyfull][hidejustifyleft][hidejustifyright][hidejustifycenter]) #vline-justify {
    display: none;
  }
/*
  :host([hideindent][hideoutdent]) #vline-indent {
    display: none;
  }
  */

  .link-actions {
    display: block;
  }

  .link-actions > ropi-touchable {
    padding: 0.75rem 1rem;
    flex: 1;
  }

  .link-actions .icon-and-button {
    display: grid;
    grid-template-columns: 3rem auto;
  }

  @media only screen and (min-width: 480px) {
    .link-inputs {
      display: grid;
      grid-template-columns: 1fr 6rem;
    }

    .link-actions {
      display: flex;
    }
  }
</style>
<div class="row topbar">
  <ropi-touchable id="cancel">
    <ropi-material-icon>close</ropi-material-icon>
  </ropi-touchable>

  <ropi-touchable id="ok">
    <ropi-material-icon>check</ropi-material-icon>
  </ropi-touchable>
</div>
<ropi-vertical-scroll>
  <div class="tools">
  <!--
    <ropi-touchable id="undo" disabled>
      <ropi-material-icon>undo</ropi-material-icon>
    </ropi-touchable>
    <ropi-touchable id="redo" disabled>
      <ropi-material-icon>redo</ropi-material-icon>
    </ropi-touchable>

    <div class="vline"></div>

    -->

    <ropi-touchable class="format-command" id="bold">
      <ropi-material-icon>format_bold</ropi-material-icon>
    </ropi-touchable>
    <ropi-touchable class="format-command" id="italic">
      <ropi-material-icon>format_italic</ropi-material-icon>
    </ropi-touchable>
    <ropi-touchable class="format-command" id="underline">
      <ropi-material-icon>format_underlined</ropi-material-icon>
    </ropi-touchable>
    <ropi-touchable class="format-command" id="strikeThrough">
      <ropi-material-icon>format_strikethrough</ropi-material-icon>
    </ropi-touchable>

    <div id="vline-basic" class="vline"></div>

    <ropi-touchable class="format-command" id="createLink">
      <ropi-material-icon>link</ropi-material-icon>
    </ropi-touchable>
    <ropi-touchable class="format-command" id="unlink">
      <ropi-material-icon>link_off</ropi-material-icon>
    </ropi-touchable>

    <div id="vline-link" class="vline"></div>

    <ropi-touchable class="format-command" id="insertUnorderedList">
      <ropi-material-icon>format_list_bulleted</ropi-material-icon>
    </ropi-touchable>
    <ropi-touchable class="format-command" id="insertOrderedList">
      <ropi-material-icon>format_list_numbered</ropi-material-icon>
    </ropi-touchable>

    <div id="vline-list" class="vline"></div>

    <ropi-touchable class="format-command" id="justifyLeft">
      <ropi-material-icon>format_align_left</ropi-material-icon>
    </ropi-touchable>
    <ropi-touchable class="format-command" id="justifyCenter">
      <ropi-material-icon>format_align_center</ropi-material-icon>
    </ropi-touchable>
    <ropi-touchable class="format-command" id="justifyRight">
      <ropi-material-icon>format_align_right</ropi-material-icon>
    </ropi-touchable>
    <ropi-touchable class="format-command" id="justifyFull">
      <ropi-material-icon>format_align_justify</ropi-material-icon>
    </ropi-touchable>

    <div id="vline-justify" class="vline"></div>
    
    <ropi-touchable class="format-command" id="indent">
      <ropi-material-icon>format_indent_increase</ropi-material-icon>
    </ropi-touchable>
    <ropi-touchable class="format-command" id="outdent">
      <ropi-material-icon>format_indent_decrease</ropi-material-icon>
    </ropi-touchable>
    
    <div id="vline-list" class="vline"></div>

    <ropi-touchable id="removeFormat">
      <ropi-material-icon>format_clear</ropi-material-icon>
    </ropi-touchable>
  </div>
</ropi-vertical-scroll>
<ropi-dialog id="link-dialog" fullwidth>
  <div slot="title">
    <ropi-translate>Create link</ropi-translate>
  </div>
  <div slot="content">
    <div class="link-inputs">
      <ropi-textfield id="href-input">
        <ropi-translate slot="placeholder">Href</ropi-translate>
      </ropi-textfield>
      <ropi-textfield id="target-input">
        <ropi-translate slot="placeholder">Target</ropi-translate>
      </ropi-textfield>
    </div>
    <div class="link-actions" style="display:none;">
      <ropi-touchable id="href-assistant-button" disabled>
        <div class="icon-and-button">
          <ropi-material-icon>location_searching</ropi-material-icon><ropi-translate>Href assistant</ropi-translate>
        </div>
      </ropi-touchable>
      <ropi-touchable id="open-href-button" disabled>
        <div class="icon-and-button">
          <ropi-material-icon>open_in_new</ropi-material-icon><ropi-translate>Open link in new tab</ropi-translate>
        </div>
      </ropi-touchable>
    </div>
  </div>
  <div slot="primary">
    <ropi-translate>Create</ropi-translate>
  </div>
  <div slot="cancel">
    <ropi-translate>Cancel</ropi-translate>
  </div>
</ropi-dialog>
<ropi-dialog id="href-assistant-dialog" fullwidth>
  <div slot="title">
    <ropi-translate>Href assistant</ropi-translate>
  </div>
  <div slot="content">
    <slot name="href-assistant"></slot>
  </div>
  <div slot="primary">
    <ropi-translate>Ok</ropi-translate>
  </div>
  <div slot="cancel">
    <ropi-translate>Back</ropi-translate>
  </div>
</ropi-dialog>
`;

customElements.define('ropi-editable-panel', RopiEditablePanelElement);

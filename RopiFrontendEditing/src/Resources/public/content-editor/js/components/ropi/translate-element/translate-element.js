import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import TypeUtil from '../type-util/type-util.js?v=1637255330';
import StringTemplate from '../string-template/string-template.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiTranslateElement extends RopiHTMLElement {

  static registerSnippets(snippets) {
    RopiTranslateElement.snippets = Object.assign(
      RopiTranslateElement.snippets,
      snippets
    );

    RopiTranslateElement.updateConnectedElements();
    RopiTranslateElement.invokeBoundCallbacks();
  }

  static updateConnectedElements() {
    for (let element of RopiTranslateElement._connectedElements) {
      element.update();
    }
  }

  static invokeBoundCallbacks() {
    for (let callback of Object.values(RopiTranslateElement._boundCallbacks)) {
      callback();
    }
  }

  static get observedAttributes() {
    return ['keys', 'vars'];
  }

  static bind(callback) {
    if (TypeUtil.isFunction(callback)) {
      RopiTranslateElement._boundCallbacks[callback] = callback;
    }
  }

  static unbind(callback) {
    delete RopiTranslateElement._boundCallbacks[callback];
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (value !== valueBefore) {
      this.update();
    }
  }

  connectedCallback() {
    RopiTranslateElement._connectedElements.push(this);

    this.update();
  }

  disconnectedCallback() {
    let elementIndex = RopiTranslateElement._connectedElements.indexOf(this);
    if (elementIndex !== -1) {
      RopiTranslateElement._connectedElements.splice(elementIndex, 1);
    }
  }

  static translate(keys, vars) {
    let context = RopiTranslateElement.snippets;

    if (typeof keys === 'string' || typeof keys === 'number') {
      let keySegments = keys.toString().split('.');
      for (let i = 0; i < keySegments.length; i++) {
        let keySegment = keySegments[i];

        if (!context[keySegment]) {
          return keys;
        }

        context = context[keySegment];
      }

      return StringTemplate.process(context, vars);
    } else if (keys instanceof Array || keys instanceof Object) {
      let translations = [];
      for (let key of Object.values(keys)) {
        translations.push(RopiTranslateElement.translate(
          key,
          vars
        ));
      }

      if (translations.length <= 1) {
        return translations[0];
      }

      let last = translations.pop();
      let and = RopiTranslateElement.translate('and');
      if (!and) {
        and = 'and';
      }

      return translations.join(', ') + ' ' + and + ' ' + last;
    }

    return keys;
  }

  static translateMultiple(keys, vars) {
    if (!TypeUtil.isTraversable(keys)) {
      return RopiTranslateElement.translate(keys, vars);
    }

    let translations = [];
    for (let key of Object.values(keys)) {
      translations.push(RopiTranslateElement.translate(
        key,
        vars
      ));
    }

    if (translations.length <= 0) {
      return '';
    }

    if (translations.length === 1) {
      return translations[0];
    }

    let last = translations.pop();
    let and = RopiTranslateElement.translate('and');
    if (!and) {
      and = 'and';
    }

    return translations.join(', ')
           + ' '
           + and
           + ' '
           + last;
  }

  update() {
    let keys = this.textContent.split(',').map(function(key) {
      return key.trim();
    });

    let vars = this.hasAttribute('vars')
               ? this._parseJson(this.getAttribute('vars'))
               : {};

    this.shadowRoot.textContent = RopiTranslateElement.translateMultiple(
      keys,
      vars
    );
  }

  _parseJson(json) {
    try { return JSON.parse(json) } catch (error) { return json; }
  }
}

RopiTranslateElement._connectedElements = [];
RopiTranslateElement._boundCallbacks = {};
RopiTranslateElement.snippets = {};
RopiTranslateElement._template = html``;

customElements.define('ropi-translate', RopiTranslateElement);

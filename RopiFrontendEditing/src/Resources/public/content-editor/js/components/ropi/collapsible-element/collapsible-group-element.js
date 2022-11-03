import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

import TypeUtil from '../type-util/type-util.js?v=1637255330';

import './collapsible-element.js?v=1637255330';

export default class RopiCollapsibleGroupElement extends RopiHTMLElement {

    constructor() {
        super();

        this._expandHandler = () => {
            this.collapseAll();
        };

        this.addEventListener('expand', this._expandHandler, true);
    }

    collapseAll() {
        for (let collapsibleElement of this.collapsibleElements) {
            collapsibleElement.collapse();
        }
    }

    get collapsibleElements() {
        let elements = [];

        for (let child of this.children) {
            if (TypeUtil.isFunction(child.collapse)) {
                elements.push(child);
            }
        }

        return elements;
    }
}

RopiCollapsibleGroupElement._template = html`
<style>
  :host {
    display: block;
  }
</style>
<slot></slot>`;

customElements.define('ropi-collapsible-group', RopiCollapsibleGroupElement);

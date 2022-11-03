import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiOnelinerElement extends RopiHTMLElement {
}

RopiOnelinerElement._template = html`
<style>
  :host {
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
    display: block;
  }
</style>
<slot></slot>
`;

customElements.define('ropi-oneliner', RopiOnelinerElement);

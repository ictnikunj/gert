import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiHlineElement extends RopiHTMLElement {
}

RopiHlineElement._template = html`
<style>
:host {
  display: block;
  border: none;
  border-bottom: solid 0.0625rem var(--ropi-color-material-50);
  margin: 0;
}
</style>
`;

customElements.define('ropi-hline', RopiHlineElement);

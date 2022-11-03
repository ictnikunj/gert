import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiHintElement extends RopiHTMLElement {
}

RopiHintElement._template = html`
<style>
  :host {
    padding: 0 var(--ropi-grid-outer-gutter-width, 1rem);
    margin-bottom: calc(var(--ropi-grid-outer-gutter-height, 0.75rem) * 2);
    display: block;
    white-space: normal;
    font-size: 0.8rem;
    color: var(--ropi-color-font-50, grey);
  }

  :host([type="indented"]) {
    padding-left: calc(var(--ropi-grid-outer-gutter-width, 1rem) + 1.5rem);
    margin: 0;
  }
</style>
<slot></slot>
`;

customElements.define('ropi-hint', RopiHintElement);

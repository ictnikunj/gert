import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiSubheaderElement extends RopiHTMLElement {
}

RopiSubheaderElement._template = html`
<style>
  :host {
    padding: var(--ropi-grid-outer-gutter-height, 0.75rem)
             var(--ropi-grid-outer-gutter-width, 1rem);
    display: block;
    font-size: 0.875rem;
    color: var(--ropi-color-font-50, grey);
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
    text-transform: uppercase;
  }
</style>
<slot></slot>
`;

customElements.define('ropi-subheader', RopiSubheaderElement);

import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiErrorElement extends RopiHTMLElement {
}

RopiErrorElement._template = html`
<style>
  :host {
    font-size: var(--ropi-font-size-m, 1rem);
    width: 100%;
    height: 100%;
    border: none;
    position: absolute;
    left: 0;
    top: 0;
    background-color: var(--ropi-color-base, black);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .box {
    text-align:center;
    max-width: 640px;
    padding: 1rem;
    box-sizing: border-box;
  }
</style>
<div class="box">
  <h1 style="font-size: var(--ropi-font-size-xxl);">:(</h1>
  <div class="message">
      <slot></slot>
  </div>
</div>
`;

customElements.define('ropi-error', RopiErrorElement);

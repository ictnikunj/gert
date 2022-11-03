import html from '../html-tag/html-tag.js?v=1637255330';

const template = html`
<style>
  :root {
    --ropi-grid-outer-gutter-height: 0.75rem;
    --ropi-grid-outer-gutter-width: 1rem;
    --ropi-grid-outer-gutter: var(--ropi-grid-outer-gutter-height) var(--ropi-grid-outer-gutter-width);

    --ropi-grid-column-gap: 1px;
    --ropi-grid-row-gap: 1px;
  }
</style>`;

document.head.appendChild(template.content);

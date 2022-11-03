import html from '../html-tag/html-tag.js?v=1637255330';

const template = html`
<style>
:root {
  --ropi-color-white: #f1f1f1;
  --ropi-color-black: #1f1f1f;
  --ropi-color-red: #ef4c4c;
  --ropi-color-orange: #ef7b4a;
  --ropi-color-yellow: #d0d000;
  --ropi-color-green: #5cb85b;
  --ropi-color-cyan: #20aca0;
  --ropi-color-blue: #6091ef;
  --ropi-color-purple: #9362ef;
  --ropi-color-pink: #e373d0;
  --ropi-color-grey: #7a7a7a;

  --ropi-color-interactive: var(--ropi-color-blue);

  --ropi-color-error: #f22;

  --ropi-color-font-100: var(--ropi-color-black);
  --ropi-color-font-75: #5d5d5d;
  --ropi-color-font-50: #8f8f8f;
  --ropi-color-font-25: #a2a3a3;
  --ropi-color-font-0: var(--ropi-color-white);

  --ropi-color-material-50: #e4e4e8;
  --ropi-color-material-25: #dadadc;

  --ropi-color-base: var(--ropi-color-white);
  --ropi-color-base-contrast-medium: var(--ropi-color-grey);
  --ropi-color-base-contrast: var(--ropi-color-black);
}
</style>`;

document.head.appendChild(template.content);

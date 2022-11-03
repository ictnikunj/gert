import html from '../../html-tag/html-tag.js?v=1637255330';

const template = html`
<style>
:root {
  --ropi-color-font-100: #ededed;
  --ropi-color-font-75: #c2c3c3;
  --ropi-color-font-50: #989898;
  --ropi-color-font-25: #4d4d4d;
  --ropi-color-font-0: #1f1f1f;

  --ropi-color-material-50: #282c34;
  --ropi-color-material-25: #21252b;

  --ropi-color-base: var(--ropi-color-black);
  --ropi-color-base-contrast-medium: var(--ropi-color-grey);
  --ropi-color-base-contrast: var(--ropi-color-white);
}
</style>`;

document.head.appendChild(template.content);

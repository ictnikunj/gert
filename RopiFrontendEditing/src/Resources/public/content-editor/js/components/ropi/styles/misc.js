import html from '../html-tag/html-tag.js?v=1637255330';

const template = html`
<style>
:root {
  --ropi-disabled-opacity: 0.33;
}
</style>`;

document.head.appendChild(template.content);

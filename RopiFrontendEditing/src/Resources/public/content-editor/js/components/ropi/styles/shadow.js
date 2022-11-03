import html from '../html-tag/html-tag.js?v=1637255330';

const template = html`
<style>
:root {
  --ropi-shadow: 0 0 0.25rem 0 rgba(0, 0, 0, 0.33);
}
</style>`;

document.head.appendChild(template.content);

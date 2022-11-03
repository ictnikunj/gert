import html from '../html-tag/html-tag.js?v=1637255330';

const template = html`
<style>
  :root {
    --ropi-breakpoint-xs: 320px;
    --ropi-breakpoint-s: 480px;
    --ropi-breakpoint-m: 640px;
    --ropi-breakpoint-l: 800px;
    --ropi-breakpoint-xl: 1024px;
    --ropi-breakpoint-xxl: 1280px;
    --ropi-breakpoint-3xl: 1920px;
  }
</style>`;

document.head.appendChild(template.content);

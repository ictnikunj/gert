import html from '../html-tag/html-tag.js?v=1637255330';

const DURATION = 301;

const template = html`
<style>
  :root {
    --ropi-animation-duration: 301ms;
  }
</style>`;

export default new class {
  get DURATION() {
    return DURATION;
  }
};

document.head.appendChild(template.content);

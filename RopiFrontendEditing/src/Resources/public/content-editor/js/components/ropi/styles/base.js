import html from '../html-tag/html-tag.js?v=1637255330';

const template = html`
<style>
/*::-webkit-scrollbar {
  width: 0;
  background: transparent;
}*/

/* Base */

html, body {
  padding: 0;
  margin: 0;
  background-color: var(--ropi-color-base);
  color: var(--ropi-color-font-100);
  font-family: var(--ropi-font-primary);
  line-height: 150%;
  font-weight: normal;

  user-select: none;
  -moz-user-select: none;
/*
  touch-action: manipulation;
  */
  -webkit-user-drag: none;
  -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
}
</style>`;

document.head.appendChild(template.content);

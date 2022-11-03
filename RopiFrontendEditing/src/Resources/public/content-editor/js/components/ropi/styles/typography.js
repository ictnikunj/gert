import html from '../html-tag/html-tag.js?v=1637255330';

const template = html`
<style>
/* TODO: embed locally */
/*@import url('https://fonts.googleapis.com/css?family=Rubik');*/

:root {
  --ropi-font-primary: Arial, Helvetica, sans-serif;

  --ropi-font-size-xs: 0.5rem;
  --ropi-font-size-s: 0.75rem;
  --ropi-font-size-m: 1rem;
  --ropi-font-size-l: 1.25rem;
  --ropi-font-size-xl: 2rem;
  --ropi-font-size-xxl: 4rem;
}
</style>`;

document.head.appendChild(template.content);

import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiLoadingElement extends RopiHTMLElement {
}

RopiLoadingElement._template = html`
<style>
  
  @keyframes ropi-loading-animation {
    0% {
      background-position: 0% 50%
    }
    100% {
      background-position: 1000% 50%
    }
  }
  
  :host {
    position: relative;
    display: block;
  }
   
  .container, 
  .bar {
    position:absolute;
    width:100%;
    height: 100%;
    display: block;
  }

  #button {
    padding: 0 var(--ropi-button-gutter-width, var(--ropi-grid-outer-gutter-width, 1rem));
  }
  
  :host([loading]) {
    background: linear-gradient(
        90deg,
        var(--ropi-color-red, #ef4c4c),
        var(--ropi-color-orange, #ef7b4a),
        var(--ropi-color-yellow, #d0d000),
        var(--ropi-color-green, #5cb85b),
        var(--ropi-color-cyan, #20aca0),
        var(--ropi-color-blue, #6091ef),
        var(--ropi-color-purple, #9362ef),
        var(--ropi-color-pink, #e373d0),
        var(--ropi-color-purple, #9362ef),
        var(--ropi-color-blue, #6091ef),
        var(--ropi-color-cyan, #20aca0),
        var(--ropi-color-green, #5cb85b),
        var(--ropi-color-yellow, #d0d000),
        var(--ropi-color-orange, #ef7b4a),
        var(--ropi-color-red, #ef4c4c)
    );
	background-size: 1000% 100%;
	animation: ropi-loading-animation 32s linear infinite;
  }
</style>
<div class="container">
  <div class="bar"></div>
</div>
<slot></slot>
`;

customElements.define('ropi-loading', RopiLoadingElement);

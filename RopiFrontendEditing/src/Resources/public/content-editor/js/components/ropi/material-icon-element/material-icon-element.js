import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';
import animation from '../styles/animation.js?v=1637255330';

const iconTemplate = html`
<div class="icon">
  <svg></svg>
</div>
`;

export default class RopiMaterialIconElement extends RopiHTMLElement {

  connectedCallback() {
    this._updateIcon(true);

    this._observer = new MutationObserver(() => {
      this._updateIcon();
    });

    this._observer.observe(this, {
      childList: true,
      characterData: true,
      subtree: true
    });
  }

  disconnectedCallback() {
    if (this._observer) {
      this._observer.disconnect();
      delete this._observer;
    }
  }

  _updateIcon(noAnimation) {
    noAnimation = this.hasAttribute('noanimation') ? true : noAnimation;

    let oldIconElements = this.shadowRoot.querySelectorAll('.icon');

    let iconName = this.textContent.trim();
    let symbolElement = document.getElementById('material-icon-sprite-' + iconName);

    let iconElement;

    if (symbolElement) {
      iconElement = iconTemplate.content.cloneNode(true).firstElementChild;

      let svgElement = iconElement.querySelector('svg');

      for (let attribute of symbolElement.attributes) {
        svgElement.setAttribute(attribute.name, attribute.value);
      }

      for (let child of symbolElement.children) {
        svgElement.appendChild(child.cloneNode(true));
      }

      svgElement.setAttribute('width', '100%');
      svgElement.setAttribute('height', '100%');

      this.shadowRoot.getElementById('container').appendChild(iconElement);
    }

    if (oldIconElements.length > 0) {
      // Icon change animation

      for (let oldIconElement of oldIconElements) {
        if (oldIconElement.classList.contains('old')) {
          continue;
        }

        let top = oldIconElement.offsetTop;
        let left = oldIconElement.offsetLeft;

        oldIconElement.classList.add('old');
        oldIconElement.style.top = top + 'px';
        oldIconElement.style.left = left + 'px';

        oldIconElement.animate([
          {transform: 'rotateZ(0)', opacity: 1},
          {transform: 'rotateZ(-90deg)', opacity: 0}
        ], {
          duration: noAnimation ? 0 : animation.DURATION,
          easing: 'ease'
        }).onfinish = () => {
          if (oldIconElement.parentNode) {
            oldIconElement.parentNode.removeChild(oldIconElement);
          }
        };
      }

      if (iconElement) {
        iconElement.animate([
          {transform: 'rotateZ(90deg)', opacity: 0},
          {transform: 'rotateZ(0)', opacity: 1}
        ], {
          duration: noAnimation ? 0 : animation.DURATION,
          easing: 'ease'
        });
      }
    }
  }
}

RopiMaterialIconElement._template = html`
<style>
  :host {
    display: inline-block;
    width: 1.5rem;
    height: 1.5rem;
  }

  #container {
    position: relative;
    display: inherit;
    height: inherit;
    width: inherit;
  }

  .icon {
    display: inherit;
    vertical-align: middle;
    position: relative;
    top: 0;
    line-height: inherit;
    color: inherit;
  }

  .icon.old {
    position: absolute;
  }

  svg {
    background-color: transparent;
    fill: currentColor;
    display: block;
  }
</style>
<span id="container">
</span>
`;

customElements.define('ropi-material-icon', RopiMaterialIconElement);

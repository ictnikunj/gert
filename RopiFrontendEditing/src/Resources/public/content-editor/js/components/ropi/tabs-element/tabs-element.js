import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

import '../vertical-scroll-element/vertical-scroll-element.js?v=1637255330';

let lastGeneratedElementId = 0;

function generateElementId(element, prefix) {
  prefix = String(prefix == null ? 'element' : prefix);
  lastGeneratedElementId++;
  return prefix + '-' + lastGeneratedElementId;
}

export default class RopiTabsElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['scrollable'];
  }

  constructor() {
    super();

    this._activeTabIndex = null;
    this._tablist = this.shadowRoot.getElementById('tablist');
    this._tabContainer = this.shadowRoot.getElementById('tab-container');

    this._resizeHandler = () => {
      this.update();
    };

    this._slotchangeHandler = () => {
      this._initTabs();
    };
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (name === 'scrollable') {
      if (this.hasAttribute('scrollable')) {
        this._tablist.removeAttribute('nocontrols');
        this.update();
      } else {
        this._tablist.setAttribute('nocontrols', '');
      }
    }
  }

  connectedCallback() {
    this._initTabs();

    window.addEventListener('resize', this._resizeHandler);

    this.shadowRoot.querySelector('slot[name="tab"]').addEventListener('slotchange', this._slotchangeHandler);
    this.shadowRoot.querySelector('slot[name="tabpanel"]').addEventListener('slotchange', this._slotchangeHandler);

    this.update();
  }

  disconnectedCallback() {
    window.removeEventListener('resize', this._resizeHandler);

    this.shadowRoot.querySelector('slot[name="tab"]').removeEventListener('slotchange', this._slotchangeHandler);
    this.shadowRoot.querySelector('slot[name="tabpanel"]').removeEventListener('slotchange', this._slotchangeHandler);
  }

  _initTabs() {
    let tabElements = this.getTabElements();
    let panelElements = this.getTabPanelElements();

    let activeIndex;
    for (let i = 0; i < tabElements.length; i++) {
      let tabElement = tabElements[i];
      let panelElement = panelElements[i];

      tabElement.setAttribute('role', 'tab');

      if (!tabElement.hasAttribute('id')) {
        tabElement.setAttribute('id', tabElement.getAttribute('id') || generateElementId(tabElement, 'tab'));
      }

      //tabElement.setAttribute('tabindex', tabElement.getAttribute('tabindex') || '0');

      if (panelElement) {
        panelElement.setAttribute('role', 'tabpanel');

        if (!tabElement.hasAttribute('aria-controls')) {
          if (!panelElement.hasAttribute('id')) {
            panelElement.setAttribute('id', panelElement.getAttribute('id') || generateElementId(panelElement, 'tabpanel'));
          }

          tabElement.setAttribute('aria-controls', panelElement.getAttribute('id'));
        }

        if (!panelElement.hasAttribute('aria-labelledby')) {
          panelElement.setAttribute('aria-labelledby', tabElement.getAttribute('id'));
        }
      }

      if (tabElement.getAttribute('aria-selected') === 'true' && activeIndex === undefined) {
        activeIndex = i;
      }

      tabElement.addEventListener('click', () => {
        this.setActiveByIndex(i);
      });
    }

    if (tabElements.length > 0) {
      activeIndex = activeIndex || 0;
      this.setActiveByIndex(activeIndex, true);
    }
  }

  get activeTabIndex() {
	   return this._activeTabIndex;
  }

  setActiveByIndex(index, noAnimation) {
    let tabElements = this.getTabElements();
    index = ((index > (tabElements.length - 1)) || index < 0) ? 0 : index;

    let indexBefore = this._activeTabIndex;
    if (indexBefore === index) {
      return;
    }

    let panelElements = this.getTabPanelElements();
    let panelElement,
        panelElementBefore,
        tabElement,
        tabElementBefore;

    if (indexBefore !== null) {
      tabElementBefore = tabElements[indexBefore];
      panelElementBefore = panelElements[indexBefore];
    }

    tabElement = tabElements[index];
    panelElement = panelElements[index];
    if (!tabElement) {
      return;
    }

    this._activeTabIndex = index;
    this._activeTabElement = tabElement;

    tabElements.forEach((tabElement) => tabElement.setAttribute('aria-selected', 'false'));
    tabElement.setAttribute('aria-selected', 'true');

    panelElements.forEach((panelElement) => {
      panelElement.setAttribute('aria-hidden', 'true');
      panelElement.style.display = 'none';
    });

    if (panelElement) {
      panelElement.setAttribute('aria-hidden', 'false');
      panelElement.style.display = 'block';
    }

    this.dispatchEvent(new CustomEvent('tabchange', {
      detail: {
        indexBefore: indexBefore,
        index: index,
        tabElement: tabElement,
        tabElementBefore: tabElementBefore || null,
        tabpanelElement: panelElement,
        tabpanelElementBefore: panelElementBefore || null
      }
    }));

    this._updateScrollPosition(noAnimation);
    this._updateSlide(noAnimation);

    let translateXFrom = '';
    if (indexBefore && index < indexBefore) {
      translateXFrom = 0 - this.offsetWidth;
    } else {
      translateXFrom = this.offsetWidth;
    }

    if (this._panelBeforeAnimation) {
      this._panelBeforeAnimation.cancel();
    }

    if (this._panelAnimation) {
      this._panelAnimation.cancel();
    }

    if (panelElementBefore) {
      panelElementBefore.style.display = 'block';
      this._panelBeforeAnimation = panelElementBefore.animate([
        {transform: 'translate(0, 0)'},
        {transform: `translate(${0 - translateXFrom}px, 0)`},
      ], {
        duration: noAnimation ? 0 : 301,
        easing: 'ease',
        fill: 'forwards'
      });

      this._panelBeforeAnimation.onfinish = () => {
        panelElementBefore.style.display = 'none';
      };
    }

    if (panelElement) {
      this._panelAnimation = panelElement.animate([
        {transform: `translate(${translateXFrom}px, 0)`},
        {transform: 'translate(0, 0)'}
      ], {
        duration: noAnimation ? 0 : 301,
        easing: 'ease',
        fill: 'forwards'
      });
    }
  }

  update() {
    this._updateSlide(true);
    this._updateScrollPosition(true);
  }

  _updateScrollPosition(noAnimation) {
    if (!this._activeTabElement || !this.hasAttribute('scrollable')) {
      return;
    }

    let tabRect = this._activeTabElement.getBoundingClientRect();
    let tabContainerRect = this._tabContainer.getBoundingClientRect();

    let tabPosX = tabRect.x - tabContainerRect.x;
    let tabWidth = tabRect.width;
    let tabContainerWidth = tabContainerRect.width;
    let scrollPosX = this._tabContainer.scrollLeft;
    let tabElementFullyVisible = (tabPosX + tabWidth - scrollPosX) < tabContainerWidth
                                  && (tabPosX - scrollPosX) > tabContainerWidth;

    if (tabElementFullyVisible) {
      return;
    }

    let scrollTo = tabPosX + tabWidth * 0.5 - tabContainerWidth * 0.5;
    if (noAnimation) {
      this._tablist.scrollPosition = scrollTo;
    } else {
      this._tablist.scrollSmooth(
        null,
        scrollTo
      );
    }
  }

  _updateSlide(noAnimation) {
    if (!this._activeTabElement) {
      return;
    }

    let slide = this.shadowRoot.getElementById('slide');
    let tabRect = this._activeTabElement.getBoundingClientRect();
    let tabContainerRect = this._tabContainer.getBoundingClientRect();
    slide.animate([
      {transform: getComputedStyle(slide).transform},
      {transform: `translate3d(${tabRect.x - tabContainerRect.x}px, 0, 0)
                  scaleX(${tabRect.width * 0.01})`}
    ], {
      duration: noAnimation ? 0 : 301,
      easing: 'ease',
      fill: 'forwards'
    });
  }

  getTabElements() {
    let slot = this.shadowRoot.querySelector('slot[name="tab"]');
    if (slot.assignedElements) {
      return slot.assignedElements();
    }

    let elements = [];

    for (let node of slot.assignedNodes()) {
      if (node instanceof HTMLElement) {
        elements.push(node);
      }
    }

    return elements;
  }

  getTabPanelElements() {
    let slot = this.shadowRoot.querySelector('slot[name="tabpanel"]');
    if (slot.assignedElements) {
      return slot.assignedElements();
    }

    let elements = [];

    for (let node of slot.assignedNodes()) {
      if (node instanceof HTMLElement) {
        elements.push(node);
      }
    }

    return elements;
  }
}

RopiTabsElement._template = html`
<style>
:host {
  display: block;
  position: absolute;
  height: 100%;
  width: 100%;
  overflow: hidden;
}

#tablist {
  position: absolute;
  height: 3rem;
  line-height: 3rem;
  width: 100%;
  z-index: 2;
  transition: opacity 301ms ease;
  opacity: 1;
  background-color: var(--ropi-color-base, white);
}

:host([tablistposition="bottom"]) > #tablist {
  bottom: 0;
}

:host([tablisthidden]) > #tablist {
  opacity: 0;
  pointer-events: none;
}

#tab-container {
  width: 100%;
  height: 100%;
  position: relative;
}

:host([scrollable]) #tab-container {
  width: auto;
}

#tab-container slot {
  display: flex;
  flex-direction: row;
}

:host([scrollable]) #tab-container slot {
  display: block;
  text-align: left;
}

#tab-container slot::slotted([role="tab"]) {
  flex: 1;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 0 var(--ropi-grid-outer-gutter-width, 1rem);
}

:host([scrollable]) #tab-container slot::slotted([role="tab"]) {
  flex: 0;
  display: inline-block;
}

#tablist slot::slotted([aria-selected="true"]) {
  color: var(--ropi-color-interactive, blue);
}

#panel-container {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
  overflow: hidden;
}

slot[name="tabpanel"]::slotted([role="tabpanel"]) {
  position: absolute;
  top: 3rem;
  left: 0;
  right: 0;
  bottom: 0;
  display: none;
  overflow: hidden;
  overflow-y: auto;
  box-sizing: border-box;
}

:host([tablistposition="bottom"]) slot[name="tabpanel"]::slotted([role="tabpanel"]) {
  top: 0;
  bottom: 3rem;
}

#slide {
  position: absolute;
  width: 100px;
  height: 0.125rem;
  background-color: var(--ropi-color-interactive, blue);
  top: 3rem;
  margin-top: -0.125rem;
  left: 0;
  transform-origin: 0 0;
}

</style>
<ropi-vertical-scroll id="tablist" nocontrols>
  <div id="tab-container" role="tablist">
    <slot name="tab"></slot>
    <div id="slide"></div>
  </div>
</ropi-vertical-scroll>
<div id="panel-container">
  <slot name="tabpanel"></slot>
</div>
`;

customElements.define('ropi-tabs', RopiTabsElement);

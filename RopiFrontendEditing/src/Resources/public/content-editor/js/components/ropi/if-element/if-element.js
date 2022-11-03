import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiIfElement extends RopiHTMLElement {

  static get observedAttributes() {
    return ['condition'];
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (value !== valueBefore) {
      this._condition = this._evalJS(this.getAttribute('condition'));
      this.evaluate();
    }
  }

  set condition(condition) {
    this._condition = condition;
    this.evaluate();
  }

  get condition() {
    return this._condition;
  }

  evaluate() {
    if (this._condition === undefined) {
      this.shadowRoot.getElementById('then').classList.add('invalid');
      this.shadowRoot.getElementById('else').classList.add('invalid');
      return;
    }

    if (this._condition) {
      this.shadowRoot.getElementById('then').classList.remove('invalid');
      this.shadowRoot.getElementById('else').classList.add('invalid');
    } else {
      this.shadowRoot.getElementById('then').classList.add('invalid');
      this.shadowRoot.getElementById('else').classList.remove('invalid');
    }
  }

  _evalJS(js) {
    try {
      return eval(js);
    } catch (error) {
      // Fail silently
    }
  }
}

RopiIfElement._template = html`
<style>
.invalid {
  display: none;
}
</style>
<slot class="invalid" name="then" id="then"></slot>
<slot class="invalid" name="else" id="else"></slot>
`;

customElements.define('ropi-if', RopiIfElement);

import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';
import '../styles/styles.js?v=1637255330';

export default class RopiCodeElement extends RopiHTMLElement  {

  connectedCallback() {
    this.update();

    this._observer = new MutationObserver(() => {
      this.update();
    });

    this._observer.observe(this, {
      childList: true,
      attributes: true,
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

  update() {
    this.shadowRoot.getElementById('code').innerText = this._adjustLeftIndentation(
      super.innerHTML
    );
  }

  _adjustLeftIndentation(string, tabWidth) {
    string = String(string).trim();
    tabWidth = parseInt(tabWidth, 10) || 2;

    let result = "";
    let lines = string.split(/\n/);

    // Find smallest indentation
    let indentation = 0;
    let numLinesWithNoIndentation = 0;
    for (let line of lines) {
      if (!line.trim()) continue; // Skip empty lines

      let match = line.match(/^([\t ]+)/);
      if (match) {
        let lineIndentation = match[0].replace(/\t/g, '  ').length;
        if (indentation === 0 || lineIndentation < indentation) {
          indentation = lineIndentation;
        }
      } else {
        numLinesWithNoIndentation++;
        if (numLinesWithNoIndentation >= 2) {
          indentation = 0;
          break;
        }
      }
    }

    // Adjust lines
    for (let line of lines) {
      result += line.replace(new RegExp('^[\t ]{0,' + indentation + '}'), '') + '\n';
    }

    return result.trim();
  }
}

RopiCodeElement._template = html`<style>
 :host {
    display: block;
    font-family: monospace;
    color: var(--ropi-color-font-75, lightgrey);
    overflow-x: auto;
    margin: 0;
    white-space: pre;
    user-select: all;
 }
</style><div id="code"></div>`;

customElements.define('ropi-code', RopiCodeElement);

import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import UUID from '../uuid/uuid.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';
import '../code-element/code-element.js?v=1637255330';
import '../styles/styles.js?v=1637255330';

export default class RopiJavaScriptTestElement extends RopiHTMLElement  {

  _dumpVariable(variable) {
    switch (typeof variable) {
      case 'string':
        return "'" + variable + "'";
      case 'object':
        return JSON.stringify(variable);
    }

    return String(variable);
  }

  _executeCodeExample(code, expect, dependencies, dontTest, noReturn) {
    return new Promise((resolve, reject) => {
      let expectedValue;

      try {
        expectedValue = eval(expect);
      } catch (error) {
        throw new Error('Inalid expect: ' + error);
      }

      let scriptElementId = 'ropi-javascript-test-' + UUID.v4();

      window[scriptElementId] = code;

      let testFunctionCode = dependencies
                            + "\n"
                            + "document.getElementById('"
                            + scriptElementId
                            + "').__callback(eval(window['" + scriptElementId + "']))";

      let scriptElement = document.createElement('script');
      scriptElement.setAttribute('id', scriptElementId);
      scriptElement.setAttribute('type', 'module');

      scriptElement.innerHTML = testFunctionCode;

      scriptElement.__callback = (returnValue) => {
        document.body.removeChild(scriptElement);
        delete window[scriptElementId];

        let codeExample = code;
        if (!noReturn) {
          codeExample += ' // Returns ' + this._dumpVariable(returnValue);
        }

        if (dontTest) {
          resolve(codeExample);
          return;
        }

        if (expectedValue === returnValue) {
          resolve(codeExample);
          return;
        }

        if (typeof expectedValue === "number" && isNaN(expectedValue) && isNaN(returnValue)) {
          resolve(codeExample);
          return;
        }

        codeExample = "[FAILED] " + codeExample + ', but expected ' + expect;
        console.log(codeExample);
        reject(codeExample);
      };

      document.body.appendChild(scriptElement);
    });
  }

  connectedCallback() {
    let dontTest = this.hasAttribute('expect') ? false : true;
    let expect = this.hasAttribute('expect') ? this.getAttribute('expect') : undefined;
    let dependencies = this.hasAttribute('dependencies') ? this.getAttribute('dependencies') : '';
    let noReturn = this.hasAttribute('noreturn');

    this._executeCodeExample(
      this.textContent.trim(),
      expect,
      dependencies,
      dontTest,
      noReturn
    ).then((result) => {
      this.shadowRoot.getElementById('code').innerHTML = result;
    }).catch((result) => {
      this.shadowRoot.getElementById('code').style.color = '#f33';
      this.shadowRoot.getElementById('code').innerHTML = result;
    });
  }
}

RopiJavaScriptTestElement._template = html`
<style>
:host {
  display: block;
  border-bottom: solid 1px var(--ropi-color-material-50, white);
}

ropi-code {
  padding: 0.5rem 0;
}

#failed {

}
</style>
<ropi-code id="code"></ropi-code>
<span id="failed"></span>`;

customElements.define('ropi-javascript-test', RopiJavaScriptTestElement);

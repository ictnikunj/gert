import RopiHTMLElement from '../html-element/html-element.js?v=1637255330';
import TypeUtil from '../type-util/type-util.js?v=1637255330';
import ObjectUtil from '../object-util/object-util.js?v=1637255330';
import DOMTemplate from '../dom-template/dom-template.js?v=1637255330';
import html from '../html-tag/html-tag.js?v=1637255330';

export default class RopiForElement extends RopiHTMLElement  {

  static get observedAttributes() {
    return ['each'];
  }

  constructor() {
    super();

    this._renderedElements = [];
  }

  attributeChangedCallback(name, valueBefore, value) {
    if (value !== valueBefore) {
      this._each = this._evalJS(this.getAttribute('each'));
      this.render();
    }
  }

  _evalJS(js) {
    try {
      return Array.from(eval(js));
    } catch (error) {
      // Fail silently
    }

    return [];
  }

  set each(each) {
    this.removeAttribute('each');
    this._each = each;
    this.render();
  }

  get each() {
    return this._each;
  }

  set parentVariables(parentVariables) {
    this._parentVariables = parentVariables;
  }

  get parentVariables() {
    return this._parentVariables;
  }

  render() {
    let each = TypeUtil.isTraversable(this._each) ? this._each : [];
    let valueVariableName = this.getAttribute('as') || 'value';
    let keyVariableName = this.getAttribute('key') || 'key';
    let iterationVariableName = this.getAttribute('iteration') || 'iteration';

    let cycle = 0;

    for (let renderedElement of this._renderedElements) {
      if (renderedElement.parentNode) {
        renderedElement.parentNode.removeChild(renderedElement);
      }
    }

    this._renderedElements = [];

    if (this.children.length > 0) {
      for (let key of Object.keys(each)) {
        let value = each[key];
        let iteration = {};

        cycle++;
        iteration.cycle = cycle;

        let clonedElements = [];

        for (let childElement of this.children) {
          if (childElement instanceof HTMLTemplateElement) {
            clonedElements = Array.from(
              childElement.content.cloneNode(true).children
            );
          } else {
            clonedElements = [childElement.cloneNode(true)];
          }

          for (let clonedElement of clonedElements) {
            let vars = {};

            if (TypeUtil.isTraversable(this._parentVariables)) {
              for (let [variableName, variableValue] of Object.entries(this._parentVariables)) {
                vars[variableName] = variableValue;
              }
            }

            vars[keyVariableName] = key;
            vars[valueVariableName] = value;
            vars[iterationVariableName] = iteration;

            // Inserting element to DOM before processing variables is important,
            // because custom elements will not be initialized otherwise
            this.parentNode.insertBefore(clonedElement, this);

            this._processVariables(clonedElement, vars);

            this._renderedElements.push(clonedElement);
          }
        }

        this.dispatchEvent(new CustomEvent('iterate', {
          detail: {
            each: each,
            as: valueVariableName,
            keyName: keyVariableName,
            value: value,
            key: key,
            elements: clonedElements,
            cycle: cycle
          }
        }));
      }
    }

    this.dispatchEvent(new CustomEvent('iterationcomplete', {
      detail: {
        each: each,
        numIterations: cycle
      }
    }));
  }

  _processVariables(element, vars) {
    DOMTemplate.process(element, vars, true, (childElement, propertyValue) => {
      if (childElement.nodeName === this.nodeName) {
        // Handle nested ropi-for element
        childElement.parentVariables = vars;
        childElement.each = propertyValue;
        childElement.parentVariables = null;
        return true;
      }
    });
  }
}

RopiForElement._template = html`<style>:host {display:none}</style>`;

customElements.define('ropi-for', RopiForElement);

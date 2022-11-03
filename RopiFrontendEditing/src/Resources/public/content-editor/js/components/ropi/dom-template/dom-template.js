import TypeUtil from '../type-util/type-util.js?v=1637255330';
import ObjectUtil from '../object-util/object-util.js?v=1637255330';
import StringTemplate from '../string-template/string-template.js?v=1637255330';

export default class DOMTemplate {

  constructor(element, vars, keepUndefinedPlaceholders, processCallback) {
    this.element = element;
    this.vars = vars;
    this.keepUndefinedPlaceholders = keepUndefinedPlaceholders;
    this.processCallback = processCallback;
  }

  set element(element) {
    this._element = (element instanceof Node) ? element : null;
  }

  get element() {
    return this._element;
  }

  set vars(vars) {
    this._vars = vars == null ? {} : vars;
  }

  get vars() {
    return this._vars;
  }

  set processCallback(processCallback) {
    this._processCallback = processCallback;
  }

  get processCallback() {
    return this._processCallback;
  }

  set keepUndefinedPlaceholders(keepUndefinedPlaceholders) {
    if (keepUndefinedPlaceholders) {
      this._keepUndefinedPlaceholders = true;
    } else {
      this._keepUndefinedPlaceholders = false;
    }
  }

  get keepUndefinedPlaceholders() {
    return this._keepUndefinedPlaceholders;
  }

  process() {
    if (!this.element) {
      return;
    }

    this.element.domVariables = this.vars;

    let dataAttributeMap = {};
    for (let variableName of Object.keys(this.vars)) {
      let dataAttributeName = 'data-' + variableName.toLowerCase();
      dataAttributeMap[dataAttributeName] = variableName;
    }

    let childElements = Array.from(this.element.querySelectorAll('*'));
    childElements.push(this.element);

    let findChildrenOfTemplateElements = (childElements) => {
      let processedChildElements = [];

      for (let childElement of childElements) {
        if (childElement instanceof HTMLTemplateElement) {
          let templateChildElements = childElement.content.querySelectorAll('*');
          processedChildElements = processedChildElements.concat(
            findChildrenOfTemplateElements(templateChildElements)
          );
        } else {
          processedChildElements.push(childElement);
        }
      }

      return processedChildElements;
    };

    childElements = findChildrenOfTemplateElements(childElements);

    for (let childElement of childElements) {
      if (!childElement.hasAttributes()) {
        continue;
      }

      for (let attribute of childElement.attributes) {
        if (dataAttributeMap[attribute.name] !== undefined) {
          let propertyName = dataAttributeMap[attribute.name];
          let propertyPath = attribute.value;
          let propertyValue = (propertyPath == null || String(propertyPath).trim() == '')
                              ? this.vars[propertyName]
                              : ObjectUtil.getKeyPath(this.vars[propertyName], propertyPath);

          let processed = false;
          if (TypeUtil.isFunction(this.processCallback)) {
            processed = this.processCallback(childElement, propertyValue);
          }

          if (!processed) {
            childElement.innerText = propertyValue;
          }

          continue;
        }

        try {
          attribute.value = StringTemplate.process(
            attribute.value,
            this.vars,
            this.keepUndefinedPlaceholders
          );
        } catch (error) {
          // Fail silently
        }
      }
    }
  }

  static process(element, vars, keepUndefinedPlaceholders, processCallback) {
    return (new DOMTemplate(
      element,
      vars,
      keepUndefinedPlaceholders,
      processCallback
    )).process();
  }
}

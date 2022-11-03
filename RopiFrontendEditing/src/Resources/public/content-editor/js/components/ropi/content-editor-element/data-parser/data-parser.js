import DOMUtil from '../../dom-util/dom-util.js?v=1637255330';
import Logger from '../../logger/logger.js?v=1637255330';
import TypeUtil from '../../type-util/type-util.js?v=1637255330';

export default new class {

  parseFromDocument(document, dontParseChildren) {
    let data = {
      type: 'document',
      meta: {
        context: this.parseDocumentContext(document)
      },
      children: []
    };

    if (!dontParseChildren) {
        this._parseDataFromElement(data, document);
    }

    return data;
  }

  parseFromElement(element, forPreset) {
    if (!element || !element.children || element.body) {
      throw('Argument element must be a valid element and not a document');
    }

    let data = this._buildDataFromRenderedContentElement(element, forPreset);

    this._parseDataFromElement(data, element, forPreset);

    return data;
  }

  parseDocumentContext(document, quiet) {
    if (!document || !document.children || !document.body) {
      throw('Argument document must be a valid document');
    }

    let documentContext = document.body.getAttribute('data-ropi-document-context');
    if (!documentContext) {
      if (!quiet) {
        Logger.logWarning(
            'Can not get document context, because body tag has no'
            + ' data-ropi-document-context attribute or data-ropi-document-context attribute is empty'
        );
      }

      return null;
    }

    let parsedDocumentContext;

    try {
      parsedDocumentContext = JSON.parse(documentContext);
    } catch (e) {
      // Silent fail
    }

    if (!TypeUtil.isObject(parsedDocumentContext)) {
      if (!quiet) {
        Logger.logWarning(
            'Can not parse document context, because data-ropi-document-context attribute'
            + ' is not a valid JSON object'
        );
      }

      return null;
    }

    return parsedDocumentContext;
  }

  _parseDataFromElement(parentData, element, forPreset) {
    let contentAreaElements = DOMUtil.closestChildren(
      element,
      '[data-ropi-content-area]'
    );

    for (let contentAreaElement of contentAreaElements) {
      let contentAreaName = contentAreaElement.getAttribute('data-ropi-content-area');
      if (!contentAreaName) {
        break;
      }

      let areaData = {
        type: 'area',
        meta: {
          name: contentAreaName
        },
        children: []
      };

      parentData.children.push(areaData);

      for (let childElement of contentAreaElement.children) {
        let elementData = this._buildDataFromRenderedContentElement(childElement, forPreset);
        if (!elementData) {
          continue;
        }

        areaData.children.push(elementData);

        this._parseDataFromElement(elementData, childElement, forPreset);
      }
    }
  }

  _buildDataFromRenderedContentElement(renderedElement, forPreset) {
    let contentElement = renderedElement.ropiContentElement;
    if (!contentElement) {
      return null;
    }

    return this.buildDataFromContentElement(contentElement, forPreset);
  }

  buildDataFromContentElement(contentElement, forPreset) {
    let meta = {
      type: contentElement.type
    };

    if (!forPreset) {
      meta['uuid'] = contentElement.uuid;
      meta['creationTimestamp'] = contentElement.creationTimestamp;
      meta['languageSpecificSettings'] = contentElement.languageSpecificSettings;
    }

    return {
      type: 'element',
      meta: meta,
      configuration: contentElement.configuration,
      contents: contentElement.contents,
      children: []
    };
  }
}

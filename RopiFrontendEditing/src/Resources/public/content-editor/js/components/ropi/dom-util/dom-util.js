export default class DOMUtil {

  static forceReflow(elements) {
    elements = Array.isArray(elements) ? elements : [elements];
    elements.forEach((element) => {
      let _ = element.clientWidth;
    });
  }

  static remToPixels(rem) {
    return rem * parseFloat(getComputedStyle(document.documentElement).fontSize);
  }

  static relativeOffsetLeft(element) {
    let offsetParent = element;
    while (offsetParent) {
      let rect = offsetParent.getBoundingClientRect();
      if (rect.left !== 0) {
        return rect.left;
      }

      offsetParent = offsetParent.offsetParent;
    }

    return 0;
  }

  static scrollParent(element) {
    let parent = element;
    while (parent) {
      if (!(parent instanceof HTMLElement)) {
        return null;
      }

      let parentStyle = getComputedStyle(parent);
      if (['scroll', 'auto'].includes(parentStyle.overflowY)
          || ['scroll', 'auto'].includes(parentStyle.overflowX)) {
            return parent;
      }

      parent = parent.parentNode;
    }

    return null;
  }

  static relativeScrollTop(element) {
    let offsetParent = element;
    while (offsetParent) {
      let offsetParentStyle = getComputedStyle(offsetParent);
      if (['scroll', 'auto'].includes(offsetParentStyle.overflowY)) {
        return offsetParent.scrollTop;
      }

      offsetParent = offsetParent.offsetParent;
    }

    return window.pageYOffset;
  }

  static prependChild(node, nodeToPrepend) {
    if (node.firstElementChild) {
      node.insertBefore(
        nodeToPrepend,
        node.firstElementChild
      );
    } else {
      node.appendChild(nodeToPrepend);
    }
  }

  static insertAfter(node, nodeToInsert) {
    if (!node.parentNode) {
      return;
    }

    if (node.nextElementSibling) {
      node.parentNode.insertBefore(
        nodeToInsert,
        node.nextElementSibling
      );
    } else {
      node.parentNode.appendChild(nodeToInsert);
    }
  }

  static querySelectorImmediateChildren(element, selector) {
    let matchedElements = [];

    for (let child of element.children) {
      if (child.matches(selector)) {
        matchedElements.push(child);
      }
    }

    return matchedElements;
  }

  static parents(element, selector) {
    let elements = [];
    let currentElement = element ? element.parentNode : null;

    while (currentElement && currentElement.matches) {
      if (selector && currentElement.matches(selector)) {
        elements.push(currentElement);
      }

      currentElement = currentElement.parentNode;
    }

    return elements;
  }

  static closestChildren(element, selector) {
    let matchedElements = [];
    closestChildren(element, selector, matchedElements);
    return matchedElements;
  }

  static unwrap(element) {
    let parent = element.parentNode;
    if (!parent) {
      return;
    }

    while (element.firstChild) {
      parent.insertBefore(element.firstChild, element);
    }

    parent.removeChild(element);
  }

  static replaceTags(element, tagName, newTagName) {
    let elementToReplace = element.querySelector(tagName);

    while (elementToReplace) {
      let newElement = document.createElement(newTagName);

      for (let attribute of elementToReplace.attributes) {
        newElement.setAttribute(attribute.name, attribute.value);
      }

      for (let childNode of elementToReplace.childNodes) {
        newElement.appendChild(childNode);
      }

      elementToReplace.parentNode.insertBefore(newElement, elementToReplace);

      elementToReplace.parentNode.removeChild(elementToReplace);

      elementToReplace = element.querySelector(tagName);
    }
  }
}

function closestChildren(element, selector, matchedElements) {
  if (element instanceof Element && element.matches(selector)) {
    matchedElements.push(element);
    return;
  }

  for (let child of element.children) {
    if (child.matches(selector)) {
      matchedElements.push(child);
    } else {
      closestChildren(child, selector, matchedElements);
    }
  }
}

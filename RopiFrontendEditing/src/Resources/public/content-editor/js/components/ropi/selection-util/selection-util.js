export default class SelectionUtil {

  static saveSelection(view) {
    if (!view) {
      view = window
    };

    let selection = view.getSelection();
    if (selection.rangeCount === 0) {
      return function() {};
    }

    let range = selection.getRangeAt(0);

    let selectionState = {
      collapsed: range.collapsed,
      endContainer: range.endContainer,
      endOffset: range.endOffset,
      startContainer: range.startContainer,
      startOffset: range.startOffset,
    };

    return function() {
      let selection = view.getSelection();
      selection.removeAllRanges();

      let range = view.document.createRange();

      let startContainer = selectionState.startContainer;
      let endContainer = selectionState.endContainer;
      let startOffset = selectionState.startOffset;
      let endOffset = selectionState.endOffset;

      if (startOffset > startContainer.length) {
        startOffset = startContainer.length;
      }

      if (endOffset > endContainer.length || startOffset > endOffset) {
        endOffset = endContainer.length;
      }

      range.setStart(startContainer, startOffset);
      range.setEnd(endContainer, endOffset);

      selection.addRange(range);
    };
  }

}

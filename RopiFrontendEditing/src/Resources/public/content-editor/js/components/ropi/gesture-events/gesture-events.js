export default class GestureEvents {

  static enableDoubleClick(element) {
    GestureEvents.disableDoubleClick(element);

    element.addEventListener('mousedown', doubleClick.mousedownHandler);
    element.addEventListener('mouseup', doubleClick.mouseupHandler);
  }

  static disableDoubleClick(element) {
    delete element._ropiGestureEventsDoubleClickTarget;
    delete element._ropiGestureEventsDoubleClickTarget;
    delete element._ropiGestureEventsDoubleClickNumClicks;

    if (element._ropiGestureEventsDoubleClickTimeout) {
      clearTimeout(element._ropiGestureEventsDoubleClickTimeout);
      delete element._ropiGestureEventsDoubleClickTimeout;
    }

    element.removeEventListener('mousedown', doubleClick.mousedownHandler);
    element.removeEventListener('mouseup', doubleClick.mouseupHandler);
  }

  static enableLongPress(element) {
    GestureEvents.disableLongPress(element);

    element.addEventListener(
      'touchstart',
      longPress.pressstartHandler,
      {passive: true}
    );

    element.addEventListener(
      'mousedown',
      longPress.pressstartHandler,
      {passive: true}
    );
  }

  static disableLongPress(element) {
    element.removeEventListener(
      'touchstart',
      longPress.pressstartHandler,
      {passive: true}
    );

    element.removeEventListener(
      'mousedown',
      longPress.pressstartHandler,
      {passive: true}
    );
  }
}

const longPress = {
  pressstartHandler: (event) => {
    let element = event.currentTarget;
    let startScreenX = event.type === 'mousedown' ? event.screenX : event.touches[0].screenX;
    let startScreenY = event.type === 'mousedown' ? event.screenY : event.touches[0].screenY;
    let longpress = false;
    let longpressTimer;

    let moveHandler = (event) => {
      let screenX = event.type === 'mousemove' ? event.screenX : event.touches[0].screenX;
      let screenY = event.type === 'mousemove' ? event.screenY : event.touches[0].screenY;

      let deltaX = Math.abs(screenX - startScreenX);
      let deltaY = Math.abs(screenY - startScreenY);

      if (deltaX > 20 || deltaY > 20) {
        clearTimeout(longpressTimer);

        element.removeEventListener('touchmove', moveHandler, {
          passive: true
        });

        element.removeEventListener('mousemove', moveHandler, {
          passive: true
        });
      }
    };

    let releaseHandler = () => {
      clearTimeout(longpressTimer);

      element.removeEventListener('touchmove', moveHandler, {
        passive: true
      });

      element.removeEventListener('mousemove', moveHandler, {
        passive: true
      });

      element.removeEventListener('touchend', releaseHandler, {
        passive: true
      });

      element.removeEventListener('mouseup', releaseHandler, {
        passive: true
      });
    };

    longpressTimer = setTimeout(() => {
      longpress = true;

      element.removeEventListener('touchmove', moveHandler, {
        passive: true
      });

      element.removeEventListener('mousemove', moveHandler, {
        passive: true
      });

      element.dispatchEvent(new CustomEvent('longpress', {
        bubbles: true,
        detail: {
          originalEvent: event
        }
      }));
    }, 444);

    element.addEventListener('touchmove', moveHandler, {
      passive: true
    });

    element.addEventListener('mousemove', moveHandler, {
      passive: true
    });

    element.addEventListener('touchend', releaseHandler, {
      passive: true
    });

    element.addEventListener('mouseup', releaseHandler, {
      passive: true
    });
  }
};

const doubleClick = {
  mousedownHandler: (event) => {
    let element = event.currentTarget;

    if (element._ropiGestureEventsDoubleClickLastClick && (Date.now() - element._ropiGestureEventsDoubleClickLastClick) > 300) {
      // Last click is older than to 300ms thats why reset num clicks to 0
      element._ropiGestureEventsDoubleClickNumClicks = 0;
    }

    element._ropiGestureEventsDoubleClickLastClick = Date.now();

    if (!element._ropiGestureEventsDoubleClickTarget) {
      element._ropiGestureEventsDoubleClickTarget = event.target;
    }

    element._ropiGestureEventsDoubleClickNumClicks = parseInt(
      element._ropiGestureEventsDoubleClickNumClicks || 0,
      10
    ) + 1;

    delete element._ropiGestureEventsDoubleClickDispatchSingle;

    if (element._ropiGestureEventsDoubleClickTimeout) {
      clearTimeout(element._ropiGestureEventsDoubleClickTimeout);
      delete element._ropiGestureEventsDoubleClickTimeout;
    }

    if (element._ropiGestureEventsDoubleClickNumClicks >= 2) {
      let eventOptions = Object.assign({
        detail: {
          originalEvent: event
        },
        bubbles: true
      }, event);

      element._ropiGestureEventsDoubleClickTarget.dispatchEvent(
        new CustomEvent('doubleclick', eventOptions)
      );

      element._ropiGestureEventsDoubleClickNumClicks = 0;
      delete element._ropiGestureEventsDoubleClickTarget;

      return;
    }
  },
  mouseupHandler: (event) => {
    let element = event.currentTarget;

    element._ropiGestureEventsDoubleClickTimeout = setTimeout(() => {
      element._ropiGestureEventsDoubleClickNumClicks = 0;

      if (element._ropiGestureEventsDoubleClickTarget) {
        let eventOptions = Object.assign({
          detail: {
            originalEvent: event
          },
          bubbles: true
        }, event);

        element._ropiGestureEventsDoubleClickTarget.dispatchEvent(
          new CustomEvent('singleclick', eventOptions)
        );

        delete element._ropiGestureEventsDoubleClickTarget;
      }
    }, 300);
  }
};

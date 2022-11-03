
export default class SmoothScroll {

  static vertical(element, from, to) {
    return new Promise((resolve) => {
      let fromMatrix = getComputedStyle(element).transform;

      if (element._ropiScrollSmoothAnimation) {
        try {
          element._ropiScrollSmoothAnimation.cancel();
        } catch (e) {
          // Silent fail: The operation can fail, if scrollSmooth() method is used
          // within a timeout function and the user focuses another browser tab.
        }
      }

      // Normalize arguments

      from = Number(from == null ? undefined : from);
      if (isNaN(from)) {
        from = element.scrollLeft;
      }

      if (from < 0) {
        from = 0;
      }

      let maxScrollPositionX = element.scrollWidth - element.offsetWidth;
      to = Number(to == null ? undefined : to);
      if (isNaN(to) || to > maxScrollPositionX) {
        to = maxScrollPositionX;
      }

      if (to < 0) {
        to = 0;
      }

      // Animate

      element.style.overflowX = 'visible';

      element._ropiScrollSmoothAnimation = element.animate([
        {transform: fromMatrix === 'none' ? 'translate3d(-' + from + 'px, 0, 0)' : fromMatrix},
        {transform: 'translate3d(-' + to + 'px, 0, 0)'}
      ], {
        duration: 301,
        easing: 'ease'
      });

      element._ropiScrollSmoothAnimation.onfinish = () => {
        let maxScrollPosition = this.maxScrollPosition;
        if (to > maxScrollPosition) {
          to = maxScrollPosition;
        }

        element.style.overflowX = '';
        element.scrollLeft = to;

        delete element._ropiScrollSmoothAnimation;

        resolve();
      };
    });
  }
}

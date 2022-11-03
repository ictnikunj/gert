(function() {
    let initAll = function() {
        let carousels = document.querySelectorAll('[data-ropi-carousel]');
        for (let i = 0; i < carousels.length; i++) {
            init(carousels[i]);
        }
    };

    let init = function(carousel) {
        if (carousel.ropiCarousel) {
            return;
        }

        carousel.ropiCarousel = new function() {
            const self = this;
            const rotationInterval = carousel.getAttribute('data-ropi-carousel-rotation-interval') || 0;
            const slides = carousel.querySelector('[data-ropi-carousel-slides]') || carousel.querySelector('ul');
            const numSlides = carousel.getAttribute('data-ropi-carousel-num-slides') || slides.childElementCount;
            const left = carousel.querySelector('[data-ropi-carousel-left]');
            const right = carousel.querySelector('[data-ropi-carousel-right]');
            const dots = carousel.querySelector('[data-ropi-carousel-dots]');
            const dotsActiveClass = (dots ? dots.getAttribute('data-ropi-carousel-dots-class-active') : null) || 'ropi-dot-active';

            let interval, intervalPaused, clientX, clientY, swipe, rect;
            let current = 0;
            let swipeable = true;

            // Listeners

            carousel.addEventListener('touchstart', function(event) {
                if (!swipeable) return;

                clientX = event.changedTouches[0].clientX;
                clientY = event.changedTouches[0].clientY;
                slides.style.transition = 'none';
                swipe = false;
            }, {passive: true});

            carousel.addEventListener('touchmove', function(event) {
                if (!swipeable) return;

                let deltaX = clientX - event.changedTouches[0].clientX;
                let deltaY = event.changedTouches[0].clientY - clientY;

                if (swipe) {
                    let transform = 'translateX(calc(' + (-current * 100) + '% - ' + deltaX + 'px))';
                    slides.style.msTransform = transform;
                    slides.style.transform = transform;
                } else {
                    if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 16) {
                        swipe = true;
                        rect = carousel.getBoundingClientRect();
                    }
                }
            }, {passive: true});

            carousel.addEventListener('touchend', function(event) {
                if (!swipeable) return;

                slides.style.transition = '';

                if (swipe) {
                    let deltaX = clientX - event.changedTouches[0].clientX;
                    let percentageX = 100 / rect.width * deltaX;

                    let next = current + Math.round(percentageX / 100);

                    if (next === current) {
                        next = current + Math.round(percentageX * 4 / 100);
                    }

                    if (next < 0) {
                        next = 0;
                    } else if (next >= numSlides) {
                        next = numSlides - 1;
                    }

                    self.slideTo(next);
                    self.resetInterval();
                    swipe = false;
                }
            }, {passive: true});

            if (left && right) {
                left.addEventListener('click', function() {
                    self.resetInterval();
                    self.slideTo(current - 1);
                });

                right.addEventListener('click', function() {
                    self.resetInterval();
                    self.slideTo(current + 1);
                });
            }

            if (dots) {
                dots.addEventListener('click', function(event) {
                    self.resetInterval();
                    let index = Array.prototype.indexOf.call(dots.children, event.target);
                    if (index > -1) {
                        self.slideTo(index);
                    }
                });
            }

            // Public

            self.resetInterval = function() {
                if (interval) {
                    clearInterval(interval);
                }

                if (!rotationInterval) {
                    return;
                }

                interval = setInterval(function() {
                    if (intervalPaused || swipe) {
                        return;
                    }

                    self.slideTo(current + 1);
                }, rotationInterval);
            };

            self.setIntervalPaused = function(value) {
                intervalPaused = value;
            };

            self.getIntervalPaused = function() {
                return intervalPaused;
            };

            self.setSwipeable = function(value) {
                swipeable = !!value;
            };

            self.getSwipeable = function() {
                return swipeable;
            };

            self.slideTo = function(next) {
                next = (next >= numSlides) ? 0 : ((next < 0) ? numSlides - 1 : next);
                let transform = 'translateX(-' + (next * 100) + '%)';
                slides.style.transform = transform;
                slides.style.msTransform = transform;
                if (dots && dots.children[next]) {
                    for (let i = 0; i < dots.children.length; i++) {
                        dots.children[i].setAttribute('class', '');
                    }

                    dots.children[next].setAttribute('class', dotsActiveClass);
                }

                current = next;
            };

            self.getCurrent = function() {
                return current;
            };

            self.getDotsElement = function() {
                return dots;
            };

            self.getLeftElement = function() {
                return left;
            };

            self.getRightElement = function() {
                return right;
            };

            self.getSlidesElement = function() {
                return slides;
            };

            // constructor

            self.resetInterval();
            self.slideTo(0);
        };

        let event;

        try {
            event = new CustomEvent('ropicarouselinitialized');
        } catch(e) {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent('ropicarouselinitialized', false, false, {});
        }

        carousel.dispatchEvent(event);
    };

    document.addEventListener('load', initAll);
    document.addEventListener('contentelementrender', initAll);

    initAll();
})();
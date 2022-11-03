import FlyoutMenuPlugin from 'src/plugin/main-menu/flyout-menu.plugin';
import DeviceDetection from 'src/helper/device-detection.helper';
import Iterator from 'src/helper/iterator.helper';
import DomAccess from 'src/helper/dom-access.helper';

export default class BrandcrockMegaMenu extends FlyoutMenuPlugin {
   
    static options = {
        /**
         * Hover debounce delay.
         */
        debounceTime: 125,

        /**
         * Class to add when the flyout is active.
         */
        activeCls: 'is-open',

        /**
         * Selector for the close buttons.
         */
        closeSelector: '.js-close-flyout-menu',

        /**
         * Id attribute for the flyout.
         * Should be the same as 'triggerDataAttribute'
         */
        flyoutIdDataAttribute: 'data-flyout-menu-id',

        /**
         * Trigger attribute for the opening elements.
         * Should be the same as 'flyoutIdDataAttribute'
         */
        triggerDataAttribute: 'data-flyout-menu-trigger',

        /**
         * Class to add effects when the flyout is open.
         */
        hoverEffectsCls: 'bchoverEffects',

        menuDataImage: 'data-image'
    };
    init() {
		
		this._debouncer = null;
		
		this._menuEls = this.el.querySelectorAll(`[${this.options.menuDataImage}]`);
     
        this._closeEls = this.el.querySelectorAll(this.options.closeSelector);
        this._flyoutEls = this.el.querySelectorAll(`[${this.options.flyoutIdDataAttribute}]`);
		
     
        super.init();
    }
    /**
     * registers all needed events
     *
     * @private
     */
    _registerEvents() {
		const clickEvent = (DeviceDetection.isTouchDevice()) ? 'touchstart' : 'click';
        const hoverEvent = (DeviceDetection.isTouchDevice()) ? 'touchstart' : 'mouseenter';
		const closeEvent = (DeviceDetection.isTouchDevice()) ? 'touchstart' : 'mouseleave';
		 
        Iterator.iterate(this._menuEls, el => {
            const imageUrl = DomAccess.getDataAttribute(el, this.options.menuDataImage);
            el.addEventListener(hoverEvent, this._imageChange.bind(this, imageUrl));
			el.addEventListener(closeEvent, () => this._debounce(this._closeAllFlyouts));
			
        });
		
		 // register closing triggers
        Iterator.iterate(this._closeEls, el => {
            el.addEventListener(clickEvent, this._closeAllFlyouts.bind(this));
        });
		
		 // register non touch events for open flyouts
        if (!DeviceDetection.isTouchDevice()) {
            Iterator.iterate(this._flyoutEls, el => {
                el.addEventListener('mousemove', () => this._clearDebounce());
                el.addEventListener('mouseleave', () => this._debounce(this._closeAllFlyouts));
            });
        }

        super._registerEvents();
    }
    /**
     * opens a single flyout
     *
     * @param {Element} flyoutEl
     * @param {Element} triggerEl
     * @private
     */
    _openFlyout(flyoutEl, triggerEl) {
        if (!this._isOpen(triggerEl)) {
            this._closeAllFlyouts();
            flyoutEl.classList.add(this.options.activeCls);
            flyoutEl.classList.add(this.options.hoverEffectsCls);
            triggerEl.classList.add(this.options.activeCls);
        }

        this.$emitter.publish('openFlyout');
    }
    /**
     * closes a single flyout
     *
     * @param {Element} flyoutEl
     * @param {Element} triggerEl
     * @private
     */
    _closeFlyout(flyoutEl, triggerEl) {
        if (this._isOpen(triggerEl)) {
            flyoutEl.classList.remove(this.options.activeCls);
            flyoutEl.classList.remove(this.options.hoverEffectsCls);
            triggerEl.classList.remove(this.options.activeCls);
            triggerEl.classList.remove(this.options.hoverEffectsCls);
        }

        this.$emitter.publish('closeFlyout');
    }
	
	
	 /**
     * opens a flyout
     *
     * @param {String} flyoutId
     * @param {Element} triggerEl
     * @param {Event} event
     *
     * @private
     */
    _openFlyoutById(flyoutId, triggerEl, event) {
        const flyoutEl = this.el.querySelector(`[${this.options.flyoutIdDataAttribute}='${flyoutId}']`);

        if (flyoutEl) {
            this._debounce(this._openFlyout, flyoutEl, triggerEl);
        }

        if (!this._isOpen(triggerEl)) {
            FlyoutMenuPlugin._stopEvent(event);
        }

        this.$emitter.publish('openFlyoutById');
    }

    /**
     * collect all flyouts
     * and close them
     *
     * @private
     */
    _closeAllFlyouts() {
        const flyoutEls = this.el.querySelectorAll(`[${this.options.flyoutIdDataAttribute}]`);

        Iterator.iterate(flyoutEls, el => {
            const triggerEl = this._retrieveTriggerEl(el);
            this._closeFlyout(el, triggerEl);
        });

        this.$emitter.publish('closeAllFlyouts');
    }
	
	
	
	
    /**
     * image change
     *
     * @param {Element} imageUrl
     * @param {Element} triggerElmenu
     * @private
     */
    _imageChange(imageUrl, triggerElmenu){
        JSON.stringify(triggerElmenu);
        $('.navigation-flyouts .navigation-flyout').each(function() {
            if($(this).hasClass('is-open')) {
                var data=$(this).attr('data-flyout-menu-id');
                if(imageUrl!=''){
                    $('.navigation-flyout-teaser-image-container').removeClass('hide');
                    $('#'+data).removeAttr('srcset');
                    $('#'+data).attr('src',imageUrl);
                    imageUrl='';
                }else{
                    $('.navigation-flyout-teaser-image-container').removeClass('hide');
                    $('#'+data).removeAttr('srcset');
                    $('#'+data).attr('src',$('#parent_'+data).attr('data-image'));
                }
            }
        });


    }
	
	/**
     *
     * @param {Element} el
     * @returns {Element}
     * @private
     */
    _retrieveTriggerEl(el) {
        const flyoutId = DomAccess.getDataAttribute(el, this.options.flyoutIdDataAttribute, false);
        return this.el.querySelector(`[${this.options.triggerDataAttribute}='${flyoutId}']`);
    }

    /**
     * returns if the element is opened or not
     *
     * @param {Element} el
     *
     * @returns {boolean}
     * @private
     */
    _isOpen(el) {
        return el.classList.contains(this.options.activeCls);
    }

    /**
     *
     * function to debounce menu
     * openings/closings
     *
     * @param {function} fn
     * @param {array} args
     *
     * @returns {Function}
     * @private
     */
    _debounce(fn, ...args) {
        this._clearDebounce();
        this._debouncer = setTimeout(fn.bind(this, ...args), this.options.debounceTime);
    }

    /**
     * clears the debounce timer
     *
     * @private
     */
    _clearDebounce() {
        clearTimeout(this._debouncer);
    }

    /**
     * prevents the passed event
     *
     * @param {Event} event
     * @private
     */
    static _stopEvent(event) {
        if (event && event.cancelable) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }
}

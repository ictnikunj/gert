export const SWAG_CMS_EXTENSIONS_QUICKVIEW_CAROUSEL = {
    CAROUSEL_ID: 'swag-cms-extensions-quickview-carousel',
    CAROUSEL_ITEM_CLASS: 'carousel-item',
    CAROUSEL_ITEM_PRODUCT_ID_ATTR: 'data-swag-cms-extensions-quickview-carousel-product-id',
};

export default class CarouselTemplateUtil {
    /**
     * @param arrowHeadLeft {string}
     * @param arrowHeadRight {string}
     * @param carouselId {string|null}
     * @param carouselItemProductIdAttribute {string|null}
     */
    constructor(
        arrowHeadLeft,
        arrowHeadRight,
        carouselId = SWAG_CMS_EXTENSIONS_QUICKVIEW_CAROUSEL.CAROUSEL_ID,
        carouselItemProductIdAttribute = SWAG_CMS_EXTENSIONS_QUICKVIEW_CAROUSEL.CAROUSEL_ITEM_PRODUCT_ID_ATTR
    ) {
        this._arrowHeadLeft = arrowHeadLeft;
        this._arrowHeadRight = arrowHeadRight;
        this._id = carouselId;
        this._carouselItemProductIdAttribute = carouselItemProductIdAttribute;
    }

    /**
     * @param items {array}
     *
     * @returns {string}
     */
    create(items) {
        return `
            <div id="${this._id}" class="carousel slide" data-interval="0">
                <div class="carousel-inner">
                    ${items.join('')}
                </div>
                ${this._createNavigationElement('prev')}
                ${this._createNavigationElement('next')}
            </div>
        `;
    }

    /**
     * @param content {string}
     * @param productId {string}
     * @param active {boolean}
     *
     * @returns {string}
     */
    createItem(content, productId, active = false) {
        return `
            <div class="${SWAG_CMS_EXTENSIONS_QUICKVIEW_CAROUSEL.CAROUSEL_ITEM_CLASS} ${active ? 'active' : ''}"
                ${this._carouselItemProductIdAttribute}="${productId}">
                ${content}
            </div>
        `;
    }

    /**
     * @param direction {'prev'|'next'}
     *
     * @returns {string}
     */
    _createNavigationElement(direction) {
        return `
            <a class="carousel-control-${direction}" href="#${this._id}" role="button" data-slide="${direction}">
                ${direction === 'prev' ? this._arrowHeadLeft : this._arrowHeadRight}
                <span class="sr-only">${direction === 'prev' ? 'Previous' : 'Next'}</span>
            </a>
        `;
    }
}

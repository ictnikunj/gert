import Plugin from 'src/plugin-system/plugin.class';

export default class Paging extends Plugin {

    static options = {};


    init() {
        this._registerEventListeners();
    }

    /**
     * Register events to handle opening the Modal OffCanvas
     * by clicking a defined trigger selector
     * @private
     */
    _registerEventListeners() {
        this.el.addEventListener('click', this._paging.bind(this));
    }

    _paging(evt) {

        if (evt.target.classList[1] === 'sisi-page-input') {
            var value = evt.target.defaultValue;
            var baseUrl = evt.target.baseURI;
            baseUrl = baseUrl.replace(/(\&p=)([0-9]+)/g, "");
            baseUrl = baseUrl + "&p=" + value;
            location.href = baseUrl;
        }
    }

}

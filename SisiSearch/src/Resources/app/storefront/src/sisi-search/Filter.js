import Plugin from 'src/plugin-system/plugin.class';
import Iterator from 'src/helper/iterator.helper';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';

export default class Filter extends Plugin {

    pro = [];

    ma = [];

    filterName = [];

    maName = [];

    p = 0;

    count = 0;

    hits = 0;

    resetText = '';

    merker = [];

    init() {
        this._registerEvents();
        this._client = new HttpClient();
    }

    /**
     * @private
     */
    _registerEvents() {
        const checkboxes = $('.filter-multi-select-checkbox');
        var $self = this;
        $(this.el).on("change", checkboxes, function (event) {
            $self._onChangeFilter();
            $self.p = 1;
        });
        this._onResetFilter();
        this._onResetAllFilter();
        this._scrollPoupUp();

    }

    _onResetAllFilter() {
        var $self = this;
        $(this.el).on("click", ".filter-reset-all", function () {
            const checkboxes = $('.filter-multi-select-properties .filter-multi-select-checkbox:checked');
            const checkboxesManufactor = $('.filter-multi-select-manufacturer .filter-multi-select-checkbox:checked');
            checkboxes.each(function (index) {
                $(this).prop('checked', false);
            });
            checkboxesManufactor.each(function (index) {
                $(this).prop('checked', false);
            });
            $self._onChangeFilter();
            $self.p = 0;
            $self.filterName = [];
            $self.maName = [];
        });

    }

    _onResetFilter() {
        var $self = this;
        $(this.el).on("click", ".filter-active-remove", function () {
            var id = $(this).data('id');
            $('#' + id).prop('checked', false);
            $self._onChangeFilter();
            $self.p = 0;
        });
    }

    /**
     * @private
     */
    _onChangeFilter() {
        const checkboxes = $('.filter-multi-select-properties .filter-multi-select-checkbox:checked');
        const checkboxesManufactor = $('.filter-multi-select-manufacturer .filter-multi-select-checkbox:checked');
        const self = this;
        this.pro = [];
        this.p = 1;
        self.filterName = [];
        self.maName = [];
        checkboxes.each(function (index) {
            var val = $(this).attr('id');
            if (!self.pro.includes(val) && val !== undefined) {
                self.pro.push(val);
                var name = $(this).parent().parent().find('label').html();
                self.filterName.push(name);
            }
        });
        this.ma = [];
        checkboxesManufactor.each(function (index) {
            var val = $(this).attr('id');
            if (!self.ma.includes(val) && val !== undefined) {
                self.ma.push(val);
                var name = $(this).parent().parent().find('label').html();
                self.maName.push(name);
            }
        });
        self.p = 0;
        self.merker = [];
        this.fetch(false);
    }

    _scrollPoupUp() {
        const self = this;
        const $selektor = $('.search-headline');
        var strScrolling = $selektor.data('scrolling');
        if (strScrolling  === 'yes') {
            document.addEventListener('scroll', function (event) {
                var elements = document.querySelectorAll('.sisi-last-row');
                for (var i = 0; i < elements.length; i++) {
                    var viewport = self.isInViewport(elements[i]);
                    if (viewport) {
                        if (self.p === 0) self.p++;
                        if (self.p === 1) self.p++;
                        if ((self.merker.indexOf(self.p)) === -1 && (self.p > 1)) {
                            self.merker.push(self.p);
                            self.fetch(true);
                        }
                    }
                }

            }, true /*Capture event*/);
        }
    }

    _get_p()
    {
        var vars = {};
        var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi,
            function(m,key,value) {
                vars[key] = value;
            });
     return vars['p'];
    }

    isInViewport(element) {
        const rect = element.getBoundingClientRect();
        if (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= ((window.innerHeight+200) || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        ) {
            element.classList.add("sisiIsvisible");
            return true;

        } else {
            return false;
        }

    }
    _setFilter(filter, ids) {
        var filterHtml = '';
        var self = this;
        var text =  $('.search-headline').data('reset');
        $.each(filter, function (key, value) {
            filterHtml = filterHtml + '<span class="filter-active"> ' + value
                + '<button class="filter-active-remove" data-id="' + ids[key] + '"> ×</button></span>';
        });
        if (filter.length > 0) {
            filterHtml = filterHtml + '<button class="filter-reset-all btn btn-sm btn-outline-danger">' + text + '</button>'
        }
        return filterHtml;
    }

    _IterateFilter(url) {

        var index = 0;
        var indexMa = 0;
        var ids = [];
        var self = this;

        Iterator.iterate(this.pro, (item) => {
            url = url + '&pro[' + index + ']=' + item;
            ids[index] = item;
            index++;
        });
        Iterator.iterate(this.ma, (item) => {
            url = url + '&ma[' + indexMa + ']=' + item;
            ids[index] = item;
            indexMa++;
            index++;
        });

        return [ids, url]
    }

    /**
     * Fetch the latest media from the Instagram account with the given count
     */
    fetch(str) {
        const self = this;
        var url = $('.search-headline').data('ajax');
        var search = '?search=' + $('.header-search-input').val();
        var filterHtml = [];
        var ids = [];
        var filter = self.filterName.concat(self.maName);
        url = url + search;
        if (str) {
            url = url + "&p=" + self.p;
        }
        var iterate = self._IterateFilter(url);
        ids = iterate[0];
        url = iterate[1];
        var itemsLoaded = $(".cms-listing-row .product-box").length;
        if ((self.count > itemsLoaded) || (self.count === 0) || (str === false)) {
            filterHtml = self._setFilter(filter, ids);
            var filterPanel = $('#filter-panel-wrapper').html();
            var loader = '<div class="cms-listing-col col-xl-12 sisi-listing-loder"><div class="loader" role="status"' +
                'style="display: inline-block; margin-left: 49%"><span class="sr-only">Loading...</span></div></div>';
            $('.row.cms-listing-row').append(loader);
            this._client.get(url, (responseText) => {
                var data = $(responseText).find('.cms-listing-row').html();
                var $hitsSelektor = $(responseText).find('.search-headline');
                var hits = $hitsSelektor.html();
                var count = $hitsSelektor.data('count');
                var checked = this.pro.concat(this.ma);
                var plast = $hitsSelektor.data('last');
                if (self.p > 1) {
                    $('.cms-listing-row').append(data);
                } else {
                    $('.cms-listing-row').html(data);
                    filterPanel = $(responseText).find('#filter-panel-wrapper').html();
                }
                $('.search-headline').html(hits).attr('data-count', count).attr('data-last', plast);
                $('.pagination-nav').html("");
                $('#filter-panel-wrapper').html(filterPanel);
                ids = [];
                $(".filter-multi-select-checkbox").each(function (i) {
                    var valItelm = $(this).val();
                    if (checked.includes(valItelm)) {
                        $(this).prop('checked', true);
                    }
                });
                self.p++;
                var iterate = self._IterateFilter(url);
                ids = iterate[0];
                url = iterate[1];
                filterHtml = self._setFilter(filter, ids);
                $('.sisi-listing-loder').remove();
                $('.filter-panel-active-container').html(filterHtml);
                self.count = count;
                var $cmslistingRow = $(".cms-listing-row .cms-listing-col");
                var len = $cmslistingRow.length;
                $cmslistingRow.each(function( index ) {
                    $(this).removeClass('sisi-last-row');
                    if (index === (len -1)) {
                        $(this).addClass('sisi-last-row')
                    }
                });
            });
        }
    }
}

import Plugin from 'src/plugin-system/plugin.class';

export default class Paging extends Plugin {


    init() {
        this._registerEventListeners();
    }

    _registerEventListeners() {
        var values = [];
        $('.sisi-high-light div').each(function(index) {
            values[index] = $(this).html();
        });
        $.each(values, function( index, value ) {
            var oldstring = $('.content-main').html();
            var newstring = oldstring.replaceAll(value, "<b>" +value+ "</b>");
            $('.content-main').html(newstring);
        });
    }
}

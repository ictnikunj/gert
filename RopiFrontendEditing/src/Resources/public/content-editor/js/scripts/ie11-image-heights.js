(function() {
    var t = function(cb) {
        var o = document.querySelectorAll('.ropi-frontend-editing-ie--auto-height');
        for (var i = 0; i < o.length; i++) { cb(o[i]); }
    };

    var s = function(el) {
        el.style.height = 'auto';
        el.style.height = el.clientHeight + 'px';
    };

    window.addEventListener('resize', function() { t(s); });

    t(function(el) {
        el.addEventListener('load', function() { s(el); });
        s(el);
    });
})();
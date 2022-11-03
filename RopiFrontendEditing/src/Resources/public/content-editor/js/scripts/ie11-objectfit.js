(function() {
    var t = function(cb) {
        var o = document.querySelectorAll('.ropi-frontend-editing-image-objectfit > img');
        for (var i = 0; i < o.length; i++) { cb(o[i]); }
    };

    var s = function(el) {
        el.style.width = '100%';
        el.style.height = 'auto';
        el.style.top = '50%';
        el.style.left = '50%';
        el.style.transform = 'translate(-50%, -50%)';

        if (el.getAttribute('class').indexOf('ropi-frontend-editing-image--cover') > -1) {
            if (el.parentNode.clientHeight > el.clientHeight) {
                el.style.width = 'auto';
                el.style.height = '100%';
            } else {
                el.style.width = '100%';
                el.style.height = 'auto';
            }
        } else {
            if (el.parentNode.clientHeight > el.clientHeight) {
                el.style.width = '100%';
                el.style.height = 'auto';
            } else {
                el.style.width = 'auto';
                el.style.height = '100%';
            }
        }
    };

    window.addEventListener('resize', function() { t(s); });

    t(function(el) {
        el.addEventListener('load', function() { s(el); });
        s(el);
    });
})();
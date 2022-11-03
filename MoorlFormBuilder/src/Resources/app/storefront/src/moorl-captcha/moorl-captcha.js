import Plugin from 'src/plugin-system/plugin.class';

export default class MoorlCaptcha extends Plugin {
    init() {
        this._registerEvents();
    }

    _registerEvents() {
        $(document).on('click', '[data-moorl-captcha]', function () {
            let imgSrc = $(this).data('src') + '?' + Date.now();
            let formId = $(this).data('moorlCaptcha');
            $(formId).attr('src', imgSrc);
        });
    }
}

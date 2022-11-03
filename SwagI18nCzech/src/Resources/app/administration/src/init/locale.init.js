const locale = 'cs-CZ';

if (Shopware.Locale.getByName(locale) === false) {
    Shopware.Locale.register(locale, {});
}

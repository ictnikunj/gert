const locale = 'fr-FR';

if (Shopware.Locale.getByName(locale) === false) {
    Shopware.Locale.register(locale, {});
}

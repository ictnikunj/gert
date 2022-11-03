const locale = 'es-ES';

if (Shopware.Locale.getByName(locale) === false) {
    Shopware.Locale.register(locale, {});
}

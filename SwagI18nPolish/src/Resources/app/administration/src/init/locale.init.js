const locale = 'pl-PL';

if (Shopware.Locale.getByName(locale) === false) {
    Shopware.Locale.register(locale, {});
}

import deDE from "./snippet/de-DE";
import enGB from "./snippet/en-GB";
import './component/acris-product-download-form';

const { Module } = Shopware;

Module.override('sw-product', {
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    }
});

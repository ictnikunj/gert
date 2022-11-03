import template from './acris-product-links-modal-form.html.twig';

const { Component, Mixin } = Shopware;

Component.register('acris-product-links-modal-form', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('placeholder')
    ],

    props: {
        product: {
            type: Object,
            required: true
        },

        productLink: {
            type: Object,
            required: true
        }
    }
});

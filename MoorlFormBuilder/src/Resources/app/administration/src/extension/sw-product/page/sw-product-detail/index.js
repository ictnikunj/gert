import template from './index.html.twig';

const {Component} = Shopware;

Component.override('sw-product-detail', {
    template,

    computed: {
        productCriteria() {
            const criteria = this.$super('productCriteria');
            criteria.addAssociation('forms');
            return criteria;
        },
    }
});

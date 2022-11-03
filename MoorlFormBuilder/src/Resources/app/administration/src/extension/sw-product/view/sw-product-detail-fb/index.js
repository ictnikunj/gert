const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;
const {mapState} = Shopware.Component.getComponentHelper();

import template from './index.html.twig';

Component.register('sw-product-detail-fb', {
    template,

    inject: ['repositoryFactory', 'context'],

    props: {
        product: {
            type: Object,
            required: true
        }
    },

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            isLoading: false
        };
    },

    computed: {
        ...mapState('swProductDetail', [
            'product'
        ]),

        productRepository() {
            return this.repositoryFactory.create('product');
        },

        product() {
            const product = Shopware.State.get('swProductDetail').product;

            return product;
        },

        formCriteria() {
            const criteria = new Criteria();

            criteria.addFilter(Criteria.equalsAny('type', [
                'cartLineItem',
                'productRequest',
                'cartExtend'
            ]));

            return criteria;
        }
    },

    methods: {
        saveProduct() {
            if (this.product) {
                this.productRepository.save(this.product, Shopware.Context.api);
            }
        }
    }
});

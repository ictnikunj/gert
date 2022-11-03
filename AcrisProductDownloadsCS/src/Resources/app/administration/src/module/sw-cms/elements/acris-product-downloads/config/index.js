import template from './sw-cms-el-config-acris-product-downloads.html.twig';
import './sw-cms-el-config-acris-product-downloads.scss';

const { Component, Mixin, Utils } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sw-cms-el-config-acris-product-downloads', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    inject: ['repositoryFactory'],

    computed: {
        productRepository() {
            return this.repositoryFactory.create('product');
        },

        productSelectContext() {
            return {
                ...Shopware.Context.api,
                inheritance: true
            };
        },

        productCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('options.group');

            return criteria;
        },

        selectedProductCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('deliveryTime');

            return criteria;
        },

        isProductPage() {
            return Utils.get(this.cmsPageState, 'currentPage.type') === 'product_detail';
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('acris-product-downloads');
        },

        onProductChange(productId) {
            if (!productId) {
                this.element.config.product.value = null;
                this.$set(this.element.data, 'productId', null);
                this.$set(this.element.data, 'product', null);
            } else {
                this.productRepository.get(productId, this.productSelectContext, this.selectedProductCriteria).then((product) => {
                    this.element.config.product.value = productId;
                    this.$set(this.element.data, 'productId', productId);
                    this.$set(this.element.data, 'product', product);
                });
            }

            this.$emit('element-update', this.element);
        },

        emitUpdateEl() {
            this.$emit('element-update', this.element);
        }

    }
});

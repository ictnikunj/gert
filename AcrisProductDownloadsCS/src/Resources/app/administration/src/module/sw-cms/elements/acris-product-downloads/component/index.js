import template from './sw-cms-el-acris-product-downloads.html.twig';
import './sw-cms-el-acris-product-downloads.scss';

const {Component, Mixin, Utils} = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sw-cms-el-acris-product-downloads', {
    template,

    mixins: [
        Mixin.getByName('cms-element'),
        Mixin.getByName('placeholder')
    ],

    computed: {
        product() {
            if (this.currentDemoEntity) {
                return this.currentDemoEntity;
            }

            if (!this.element.data || !this.element.data.product)
                return false;

            return Utils.get(this.element, 'data.product', null);
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
            this.initElementData('acris-product-downloads');

            if(this.isProductPage){
                this.element.config.product.value = "product";
            }
        },
    }
});

const {Component, Mixin} = Shopware;
import template from './index.html.twig';

Component.register('sw-cms-el-moorl-form-builder', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    computed: {
        form() {
            if (!this.element.data || !this.element.data.form) {
                return null;
            }

            return this.element.data.form;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('moorl-form-builder');
            this.initElementData('moorl-form-builder');
        }
    }
});

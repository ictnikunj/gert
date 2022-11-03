const { Component, Mixin } = Shopware;
const Criteria = Shopware.Data.Criteria;
import template from './index.html.twig';
import './index.scss';

Component.register('sw-cms-el-config-moorl-form-builder', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    inject: ['repositoryFactory'],

    computed: {
        defaultCriteria() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('type', 'cms'));
            criteria.addFilter(Criteria.equals('active', true));
            return criteria;
        },
        formRepository() {
            return this.repositoryFactory.create('moorl_form');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('moorl-form-builder');
            this.initElementData('moorl-form-builder');
        },
        onChangeForm(formId) {
            if (!formId) {
                this.element.config.form.value = null;
                this.$set(this.element.data, 'form', null);
            } else {
                const criteria = new Criteria();

                this.formRepository.get(formId, Shopware.Context.api, criteria).then((form) => {
                    this.element.config.form.value = formId;
                    this.$set(this.element.data, 'form', form);
                });
            }

            this.$emit('element-update', this.element);
        },
    }
});

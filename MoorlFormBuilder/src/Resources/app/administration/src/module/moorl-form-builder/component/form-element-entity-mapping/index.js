const {Component} = Shopware;

import template from './index.html.twig';

Component.register('moorl-form-element-entity-mapping', {
    template,

    props: {
        formElement: {
            type: Object,
            required: true
        },
        options: {
            type: Array,
            required: true
        },
        disabled: {
            type: Boolean,
            required: true
        }
    },

    computed: {},

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {},
    }
});

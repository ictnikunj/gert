const {Component} = Shopware;

import template from './index.html.twig';

Component.register('moorl-form-element-settings', {
    template,

    props: {
        formElement: {
            type: Object,
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

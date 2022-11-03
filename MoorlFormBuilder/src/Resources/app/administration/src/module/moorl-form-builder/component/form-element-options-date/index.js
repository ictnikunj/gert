const {Component} = Shopware;
const {merge, cloneDeep} = Shopware.Utils.object;

import template from './index.html.twig';

Component.register('moorl-form-element-options-date', {
    template,

    props: {
        formElement: {
            type: Object,
            required: true
        },
        locale: {
            type: Object,
            required: true
        }
    },

    computed: {
        weekdays() {
            return [
                {
                    'value': 6,
                    'label': this.$tc('moorl-foundation.days.saturday')
                },
                {
                    'value': 0,
                    'label': this.$tc('moorl-foundation.days.sunday')
                },
                {
                    'value': 1,
                    'label': this.$tc('moorl-foundation.days.monday')
                },
                {
                    'value': 2,
                    'label': this.$tc('moorl-foundation.days.tuesday')
                },
                {
                    'value': 3,
                    'label': this.$tc('moorl-foundation.days.wednesday')
                },
                {
                    'value': 4,
                    'label': this.$tc('moorl-foundation.days.thursday')
                },
                {
                    'value': 5,
                    'label': this.$tc('moorl-foundation.days.friday')
                }
            ]
        },

        defaultProperties() {
            return {
                'dateMin': '+3 days',
                'dateMax': '+6 months',
                'dateStep': 1,
                'dateExclude': [6,0],
                'timeMin': 8,
                'timeMax': 16,
                'timeStep': 120
            }
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            for (const [key, value] of Object.entries(this.defaultProperties)) {
                if (!this.formElement[key]) {
                    this.$set(this.formElement, key, value);
                }
            }
        }
    }
});

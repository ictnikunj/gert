const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

import template from './index.html.twig';

Component.register('moorl-form-builder-appointment', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    props: {
        productId: {
            type: String,
            required: false,
            default: null
        }
    },

    data() {
        return {
            items: [],
            isLoading: true,
            locale: null
        };
    },

    computed: {
        repository() {
            return this.repositoryFactory.create('moorl_form_appointment');
        },

        languageRepository() {
            return this.repositoryFactory.create('language');
        },

        columns() {
            return [{
                property: 'active',
                label: this.$tc('moorl-form-builder.properties.active'),
                inlineEdit: 'boolean',
                align: 'center',
                width: '50px',
                allowResize: true
            }, {
                property: 'start',
                label: this.$tc('moorl-form-builder.properties.start'),
                routerLink: 'moorl.form.builder.detail',
                width: '100px',
                primary: true,
                allowResize: true
            }, {
                property: 'customer.lastName',
                label: this.$tc('moorl-form-builder.properties.customer'),
                allowResize: true
            }, {
                property: 'order.orderNumber',
                label: this.$tc('moorl-form-builder.properties.order'),
                allowResize: true
            }, {
                property: 'product.name',
                label: this.$tc('moorl-form-builder.properties.product'),
                allowResize: true
            }, {
                property: 'form.name',
                label: this.$tc('moorl-form-builder.properties.form'),
                allowResize: true
            }];
        },

        today() {
            const date = new Date();
            date.setHours(0, 0, 0, 0);

            return date.toISOString();
        },

        defaultCriteria() {
            const defaultCriteria = new Criteria();

            defaultCriteria
                .addAssociation('form')
                .addAssociation('order')
                .addAssociation('product')
                .addAssociation('customer');

            defaultCriteria.addSorting(Criteria.sort('start', 'ASC'));

            defaultCriteria.addFilter(Criteria.range('start', { gte: this.today }));

            if (this.productId) {
                defaultCriteria.addFilter(Criteria.equals('productId', this.productId));
            }

            return defaultCriteria;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            const criteria = new Criteria();
            criteria.addAssociation('locale');

            this.languageRepository
                .get(Shopware.Context.api.languageId, Shopware.Context.api, criteria)
                .then((entity) => {
                    this.locale = entity.locale;
                });

            await this.getList();
        },

        async getList() {
            this.isLoading = true;

            try {
                const items = await this.repository.search(this.defaultCriteria, Shopware.Context.api);

                this.total = items.total;
                this.tax = items;
                this.isLoading = false;
                this.items = items;
                this.selection = {};
            } catch {
                this.isLoading = false;
            }
        }
    }
});



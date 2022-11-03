const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

import template from './index.html.twig';

Component.register('moorl-form-builder-history-list', {
    template,

    inject: [
        'repositoryFactory',
        'numberRangeService'
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            detailRoute: 'moorl.form.builder.history.detail',
            items: null,
            locale: null,
            naturalSorting: true,
            showDeleteModal: false,
            filterLoading: false,
            filterCriteria: [],
            form: null,
            formDetails: null,
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            showImportModal: false,
            isLoading: true,
            selectedFile: null,
            isImporting: false,
            showModal: null
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        repository() {
            return this.repositoryFactory.create('moorl_form_history');
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        columns() {
            return [{
                property: 'createdAt',
                dataIndex: 'createdAt',
                label: this.$t('moorl-form-builder.properties.createdAt'),
                primary: true
            }, {
                property: 'form.name',
                dataIndex: 'name',
                label: this.$t('moorl-form-builder.properties.name')
            }, {
                property: 'form.type',
                dataIndex: 'type',
                label: this.$t('moorl-form-builder.properties.type')
            }, {
                property: 'form.action',
                dataIndex: 'action',
                label: this.$t('moorl-form-builder.properties.action'),
            }, {
                property: 'salesChannel.name',
                dataIndex: 'name',
                label: this.$t('moorl-form-builder.properties.salesChannel'),
            }, {
                property: 'email',
                dataIndex: 'email',
                label: this.$t('moorl-form-builder.properties.emailReceiver')
            }];
        },

        defaultCriteria() {
            const defaultCriteria = new Criteria(this.page, this.limit);
            this.naturalSorting = this.sortBy === 'name';
            defaultCriteria.setTerm(this.term);
            this.sortBy.split(',').forEach(sortBy => {
                defaultCriteria.addSorting(Criteria.sort(sortBy, this.sortDirection, this.naturalSorting));
            });

            this.filterCriteria.forEach(filter => {
                defaultCriteria.addFilter(filter);
            });

            return defaultCriteria;
        },
    },

    created() {},

    methods: {
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
        },

        showDetails(item) {
            this.showModal = true;
            this.formDetails = item;
        },

        onCloseModal() {
            this.showModal = null;
            this.formDetails = null;
        },

        updateSelection() {},

        updateTotal({total}) {
            this.total = total;
        }
    }
});

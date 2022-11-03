const { Component } = Shopware;
const { StateDeprecated } = Shopware;
const { Mixin } = Shopware;
const { Criteria } = Shopware.Data;

import template from './acris-product-download-tab-detail.html.twig';

Component.register('acris-product-download-tab-detail', {
    template,

    inject: ['repositoryFactory', 'context'],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            item: null,
            isLoading: false,
            processSuccess: false,
            repository: null,
            isSaveSuccessful: false,
            highestInternalId: null
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        tabCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('rules');
            return criteria;
        },

        languageStore() {
            return StateDeprecated.getStore('language');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent(){
            this.repository = this.repositoryFactory.create('acris_download_tab');
            this.isLoading = true;
            this.loadAll().then(() => {
                this.afterLoad();
                this.isLoading = false;
            });
        },

        getEntity() {
            this.repository
                .get(this.$route.params.id, Shopware.Context.api, this.tabCriteria)
                .then((entity) => {
                    this.item = entity;
                });
        },

        loadAll() {
            return Promise.all([
                this.loadHighestInternalId()
            ]);
        },

        afterLoad() {
            this.isLoading = false;
        },

        loadHighestInternalId() {
            const criteria = new Criteria();
            criteria.setLimit(1);
            criteria.addSorting(Criteria.sort('internalId', 'DESC'));

            this.repository.search(criteria, Shopware.Context.api).then((items) => {
                if (items.first()) {
                    this.highestInternalId = parseInt(items.first().internalId.replace('downloadgroup_', ''));
                }
                return this.getEntity();
            }).catch(() => {
                this.isLoading = false;
            });
        },

        onClickSave() {
            this.isLoading = true;
            const titleSaveError = this.$tc('acris-product-download-tab.detail.titleSaveError');
            const messageSaveError = this.$tc('acris-product-download-tab.detail.messageSaveError');
            const titleSaveSuccess = this.$tc('acris-product-download-tab.detail.titleSaveSuccess');
            const messageSaveSuccess = this.$tc('acris-product-download-tab.detail.messageSaveSuccess');

            this.isSaveSuccessful = false;
            this.isLoading = true;

            this.repository
                .save(this.item, Shopware.Context.api)
                .then(() => {
                    this.getEntity();
                    this.isLoading = false;
                    this.processSuccess = true;
                    this.createNotificationSuccess({
                        title: titleSaveSuccess,
                        message: messageSaveSuccess
                    });
                }).catch(() => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: titleSaveError,
                        message: messageSaveError
                });
            });
        },

        saveFinish() {
            this.processSuccess = false;
        },

        onChangeLanguage() {
            this.getEntity();
        },
    }
});

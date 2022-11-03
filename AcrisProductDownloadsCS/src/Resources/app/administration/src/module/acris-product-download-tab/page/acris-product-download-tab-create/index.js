const { Component } = Shopware;
const utils = Shopware.Utils;

import template from './acris-product-download-tab-create.html.twig';

Component.extend('acris-product-download-tab-create', 'acris-product-download-tab-detail', {
    template,

    beforeRouteEnter(to, from, next) {
        if (to.name.includes('acris.product.download.tab.create') && !to.params.id) {
            to.params.id = utils.createId();
            to.params.newItem = true;
        }

        next();
    },

    methods: {
        getEntity() {
            this.item = this.repository.create(Shopware.Context.api);
            this.item.priority = 10;
            const value = this.highestInternalId ? this.highestInternalId + 1 : null
            this.item.internalId = value ? 'downloadgroup_' + value : 'downloadgroup_1'
        },

        createdComponent() {
            if (!Shopware.State.getters['context/isSystemDefaultLanguage']) {
                Shopware.State.commit('context/resetLanguageToDefault');
            }

            this.$super('createdComponent');
        },

        saveFinish() {
            this.isSaveSuccessful = false;
            this.$router.push({ name: 'acris.product.download.tab.detail', params: { id: this.item.id } });
        },

        onClickSave() {
            this.isLoading = true;
            const titleSaveError = this.$tc('acris-product-download-tab.detail.titleSaveError');
            const messageSaveError = this.$tc('acris-product-download-tab.detail.messageSaveError');
            const titleSaveSuccess = this.$tc('acris-product-download-tab.detail.titleSaveSuccess');
            const messageSaveSuccess = this.$tc('acris-product-download-tab.detail.messageSaveSuccess');

            this.repository
                .save(this.item, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.createNotificationSuccess({
                        title: titleSaveSuccess,
                        message: messageSaveSuccess
                    });
                    this.$router.push({ name: 'acris.product.download.tab.detail', params: { id: this.item.id } });
                }).catch(() => {

                this.isLoading = false;
                    this.createNotificationError({
                        title: titleSaveError,
                        message: messageSaveError
                    });
                });
        }
    }
});

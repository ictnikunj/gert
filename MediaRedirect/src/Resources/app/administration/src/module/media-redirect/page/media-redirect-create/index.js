const { Component } = Shopware;

Component.extend('media-redirect-create', 'media-redirect-detail', {
    data() {
        return {
            isNew: true
        }
    },

    methods: {
        getMediaredirect() {
            this.mediaredirect = this.repository.create(Shopware.Context.api);
        },

        onClickSave() {
            this.isLoading = true;
            while (this.mediaredirect.url.charAt(0) === '/') {
                this.mediaredirect.url = this.mediaredirect.url.substring(1);
            }
            this.mediaredirect.url = '/'+this.mediaredirect.url;
            this.repository
                .save(this.mediaredirect, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.$router.push({ name: 'media.redirect.detail', params: { id: this.mediaredirect.id } });
                }).catch((exception) => {
                    this.isLoading = false;

                    this.createNotificationError({
                        title: this.$t('media-redirect.detail.errorTitle'),
                        message: exception
                    });
                });
        }
    }
});

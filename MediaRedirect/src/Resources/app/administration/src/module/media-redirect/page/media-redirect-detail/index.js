import template from './media-redirect-detail.html.twig';

const { Component, Mixin } = Shopware;

Component.register('media-redirect-detail', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            mediaredirect: null,
            isLoading: false,
            processSuccess: false,
            repository: null,
            isNew: false
        };
    },

    created() {
        this.repository = this.repositoryFactory.create('ict_media_redirect');
        this.getMediaredirect();
    },

    methods: {

        getMediaredirect() {
            this.repository
                .get(this.$route.params.id, Shopware.Context.api)
                .then((entity) => {
                    this.mediaredirect = entity;
                });
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
                    this.getMediaredirect();
                    this.isLoading = false;
                    this.processSuccess = true;
                }).catch((exception) => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: this.$t('media-redirect.detail.errorTitle'),
                        message: exception
                    });
                });
        },

        saveFinish() {
            this.processSuccess = false;
        },


    }
});

import template from './sw-product-detail.html.twig';

const { Component } = Shopware;

Component.override('sw-product-detail', {
    template,

    computed: {
        productCriteria() {
            const criteria = this.$super('productCriteria');
            criteria.addAssociation('acrisDownloads.downloadTab');
            criteria.addAssociation('acrisLinks.languages');
            return criteria;
        }
    },

    methods: {
        onAddDownloadItemToProduct(mediaItem) {
            if (this._checkIfDownloadMediaIsAlreadyUsed(mediaItem.id)) {
                this.createNotificationInfo({
                    message: this.$tc('sw-product.mediaForm.errorMediaItemDuplicated')
                });
                return false;
            }

            this.addDownloadMedia(mediaItem).then((mediaId) => {
                this.$root.$emit('download-media-added', mediaId);
                return true;
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('sw-product.mediaForm.errorHeadline'),
                    message: this.$tc('sw-product.mediaForm.errorMediaItemDuplicated')
                });

                return false;
            });
            return true;
        },

        addDownloadMedia(mediaItem) {
            Shopware.State.commit('swProductDetail/setLoading', ['media', true]);

            // return error if media exists
            if (this.product.extensions.acrisDownloads.has(mediaItem.id)) {
                Shopware.State.commit('swProductDetail/setLoading', ['media', false]);
                // eslint-disable-next-line prefer-promise-reject-errors
                return Promise.reject('A media item with this id exists');
            }

            const newMedia = this.mediaRepository.create(Shopware.Context.api);
            newMedia.mediaId = mediaItem.id;

            return new Promise((resolve) => {
                // if no other media exists
                if (this.product.extensions.acrisDownloads.length === 0) {
                    // set media item position 0
                    newMedia.position = 0;
                }
                this.product.extensions.acrisDownloads.add(newMedia);

                Shopware.State.commit('swProductDetail/setLoading', ['media', false]);

                resolve(newMedia.mediaId);
                return true;
            });
        },

        _checkIfDownloadMediaIsAlreadyUsed(mediaId) {
            return this.product.extensions.acrisDownloads.some((productMedia) => {
                return productMedia.mediaId === mediaId;
            });
        }
    }
});

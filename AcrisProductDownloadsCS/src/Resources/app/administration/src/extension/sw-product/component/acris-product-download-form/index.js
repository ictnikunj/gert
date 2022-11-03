import template from './acris-product-download-form.html.twig';
import './acris-product-download-form.scss';

const { Component, Mixin, StateDeprecated } = Shopware;
const { mapGetters } = Shopware.Component.getComponentHelper();

Component.register('acris-product-download-form', {
    template,
    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    props: {
        disabled: {
            type: Boolean,
            required: false,
            default: false
        },

        isInherited: {
            type: Boolean,
            required: false,
            default: false
        }
    },

    data() {
        return {
            isMediaLoading: false,
            columnCount: 7,
            acrisUploadTag: 'acrisProductDownloadsUploadTag',
            columnWidth: 90,
            displayEditDownload: false
        };
    },

    computed: {
        product() {
            const state = Shopware.State.get('swProductDetail');

            if (this.isInherited) {
                return state.parentProduct;
            }

            return state.product;
        },

        downloadItems() {
            const downloadItems = this.productDownloads.slice();
            const placeholderCount = this.getPlaceholderCount(this.columnCount);

            if (placeholderCount === 0) {
                return downloadItems;
            }

            for (let i = 0; i < placeholderCount; i += 1) {
                downloadItems.push(this.createPlaceholderMedia(downloadItems));
            }
            return downloadItems;
        },

        ...mapGetters('swProductDetail', {
            isStoreLoading: 'isLoading'
        }),

        isLoading() {
            return this.isMediaLoading || this.isStoreLoading;
        },

        productDownloadsRepository() {
            return this.repositoryFactory.create('acris_product_download');
        },

        productDownloads() {
            if (!this.product) {
                return [];
            }
            return this.product.extensions.acrisDownloads;
        },

        gridAutoRows() {
            return `grid-auto-rows: ${this.columnWidth}`;
        },

        displayEditDownload(displayEditDownload) {
            if(!displayEditDownload) displayEditDownload = this.displayEditDownload;
            return true;
        },

        productDownloadsEntityName() {
            return 'acris_product_download';
        }
    },

    methods: {
        onOpenMedia() {
            this.$emit('media-open');
        },

        updateColumnCount() {
            this.$nextTick(() => {
                if (this.isLoading) {
                    return false;
                }

                const cssColumns = window.getComputedStyle(this.$refs.grid, null)
                    .getPropertyValue('grid-template-columns')
                    .split(' ');
                this.columnCount = cssColumns.length;
                this.columnWidth = cssColumns[0];

                return true;
            });
        },

        getPlaceholderCount(columnCount) {
            if (this.productDownloads.length + 3 < columnCount * 2) {
                columnCount *= 2;
            }

            let placeholderCount = columnCount;

            if (this.productDownloads.length !== 0) {
                placeholderCount = columnCount - ((this.productDownloads.length) % columnCount);
                if (placeholderCount === columnCount) {
                    return 0;
                }
            }

            return placeholderCount;
        },

        createPlaceholderMedia(downloadItems) {
            return {
                isPlaceholder: true,
                media: {
                    isPlaceholder: true,
                    name: ''
                },
                mediaId: downloadItems.length.toString()
            };
        },

        successfulUpload({ targetId }) {
            // on replace
            if (this.product.extensions.acrisDownloads.find((productDownloads) => productDownloads.mediaId === targetId)) {
                return;
            }

            const productDownloads = this.createDownloadAssociation(targetId);
            this.product.extensions.acrisDownloads.add(productDownloads);
        },

        createDownloadAssociation(targetId) {
            const productDownloads = this.productDownloadsRepository.create(Shopware.Context.api);

            productDownloads.productId = this.product.id;
            productDownloads.mediaId = targetId;

            if (this.product.extensions.acrisDownloads.length <= 0) {
                productDownloads.position = 0;
            } else {
                productDownloads.position = this.product.extensions.acrisDownloads.length;
            }
            return productDownloads;
        },

        onUploadFailed(uploadTask) {
            const toRemove = this.product.extensions.acrisDownloads.find((productDownloads) => {
                return productDownloads.mediaId === uploadTask.targetId;
            });
            if (toRemove) {
                this.product.extensions.acrisDownloads.remove(toRemove.id);
            }
            this.product.isLoading = false;
        },

        removeFile(downloadItem) {
            this.product.extensions.acrisDownloads.remove(downloadItem.id);
        },

        editFile(downloadItem) {
            this.editDownloadItem = downloadItem;
            this.displayEditDownload = true;
        },

        onEditDownloadSave(downloadItem) {
            let editedDownloadItem = this.product.extensions.acrisDownloads.find((productDownloads) => productDownloads.id === downloadItem.id);
            editedDownloadItem = downloadItem;
            this.displayEditDownload = false;
        },

        onEditDownloadClose() {
            this.displayEditDownload = false;
        },

        onDropDownload(dragData) {
            if (this.product.extensions.acrisDownloads.find((productDownloads) => productDownloads.mediaId === dragData.id)) {
                return;
            }

            const productDownloads = this.createDownloadAssociation(dragData.mediaItem.id);

            this.product.extensions.acrisDownloads.add(productDownloads);
        },

        onDownloadItemDragSort(dragData, dropData, validDrop) {
            if (validDrop !== true) {
                return;
            }
            this.product.extensions.acrisDownloads.moveItem(dragData.position, dropData.position);

            this.updateDownloadItemPositions();
        },

        updateDownloadItemPositions() {
            this.productDownloads.forEach((medium, index) => {
                medium.position = index;
            });
        }
    }
});

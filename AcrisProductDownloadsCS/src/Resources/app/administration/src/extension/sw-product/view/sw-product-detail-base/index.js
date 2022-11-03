import template from './sw-product-detail-base.html.twig';
import './sw-product-detail-base.scss';

const { Criteria } = Shopware.Data;

const { Component, Context, Utils, Mixin } = Shopware;
const { isEmpty } = Utils.types;
Component.override('sw-product-detail-base', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            showDownloadModal: false,
            downloadDefaultFolderId: null,
            showDeleteUrlModal: false,
            urlSortProperty: null,
            urlSortDirection: '',
            currentUrl: null
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        mediaDefaultFolderRepository() {
            return this.repositoryFactory.create('media_default_folder');
        },
        productRepository() {
            return this.repositoryFactory.create('product');
        },
        downloadDefaultFolderCriteria() {
            const criteria = new Criteria(1, 1);

            criteria.addAssociation('folder');
            criteria.addFilter(Criteria.equals('entity', 'acris_product_download'));

            return criteria;
        },
        productDownloadRepository() {
            return this.repositoryFactory.create('acris_product_download');
        },
        mediaRepository() {
            return this.repositoryFactory.create('media');
        },
        productDownloadsRepository() {
            return this.repositoryFactory.create(this.product.extensions.acrisDownloads.entity);
        },
        productLinksRepository() {
            return this.repositoryFactory.create(this.product.extensions.acrisLinks.entity);
        },
        urlColumns() {
            return this.getUrlColumns();
        },
    },

    methods: {
        getUrlColumns() {
            return [{
                property: 'url',
                label: this.$tc('sw-product.detailBase.acrisProductLinks.columnUrl')
            }, {
                property: 'target',
                label: this.$tc('sw-product.detailBase.acrisProductLinks.columnLinkTarget')
            }, {
                property: 'title',
                label: this.$tc('sw-product.detailBase.acrisProductLinks.columnTitle')
            }, {
                property: 'description',
                label: this.$tc('sw-product.detailBase.acrisProductLinks.columnDescription')
            }];
        },

        setUrlSorting(column) {
            this.urlSortProperty = column.property;

            let direction = 'ASC';
            if (this.urlSortProperty === column.dataIndex) {
                if (this.urlSortDirection === direction) {
                    direction = 'DESC';
                }
            }
            this.urlSortProperty = column.dataIndex;
            this.urlSortDirection = direction;
        },

        onCreateNewUrl() {
            this.showAddUrlModal = true;
            this.createNewProductUrl();
        },

        createNewProductUrl() {
            const newUrl = this.productLinksRepository.create(Shopware.Context.api);
            newUrl.productId = this.product.id;
            newUrl.position = this.product.extensions.acrisLinks.length + 1;
            newUrl.linkTarget = true;

            this.currentUrl = newUrl;
        },

        onSaveUrl() {
            if (this.currentUrl === null) {
                return;
            }

            let url = this.product.extensions.acrisLinks.get(this.currentUrl.id);

            if (typeof url === 'undefined' || url === null) {
                url = this.productLinksRepository.create(Shopware.Context.api, this.currentUrl.id);
            }

            Object.assign(url, this.currentUrl);

            if (!this.product.extensions.acrisLinks.has(url.id)) {
                this.product.extensions.acrisLinks.push(url);
            }

            this.currentUrl = null;
            this.$refs.productLinkGrid.createdComponent();
        },

        onCloseUrlModal() {

            if (this.$route.query.hasOwnProperty('detailId')) {
                this.$route.query.detailId = null;
            }

            this.currentUrl = null;
        },

        onEditUrl(id) {
            const currentUrl = this.productLinksRepository.create(Shopware.Context.api, id);

            Object.assign(currentUrl, this.product.extensions.acrisLinks.get(id));

            this.currentUrl = currentUrl;

            this.showEditUrlModal = id;
        },

        onDeleteUrl(id) {
            this.showDeleteUrlModal = id;
        },

        onConfirmDeleteUrl(id) {
            this.onCloseDeleteUrlModal();

            // link have to be removed after the modal is closed because otherwise
            // the slot does not exist anymore and the modal stays open
            this.$nextTick(() => {
                this.product.extensions.acrisLinks.remove(id);
            });
            this.$refs.productLinkGrid.createdComponent();

        },

        onCloseDeleteUrlModal() {
            this.showDeleteUrlModal = false;
        },

        createdComponent() {
            this.getDownloadDefaultFolderId().then((downloadDefaultFolderId) => {
                this.downloadDefaultFolderId = downloadDefaultFolderId;
            });
        },

        getDownloadDefaultFolderId() {
            return this.mediaDefaultFolderRepository.search(this.downloadDefaultFolderCriteria, Context.api)
                .then((downloadDefaultFolder) => {
                    const defaultFolder = downloadDefaultFolder.first();

                    if (defaultFolder.folder && defaultFolder.folder.id) {
                        return defaultFolder.folder.id;
                    }

                    return null;
                });
        },

        onOpenDownloadModal() {
            this.showDownloadModal = true;
        },

        onCloseDownloadModal() {
            this.showDownloadModal = false;
        },

        onAddDownload(download) {
            if (isEmpty(download)) {
                return;
            }

            download.forEach((item) => {
                this.addDownload(item).catch(({ fileName }) => {
                    this.createNotificationError({
                        message: this.$tc('sw-product.mediaForm.errorMediaItemDuplicated', 0, { fileName })
                    });
                });
            });
        },

        addDownload(download) {
            if (this.isExistingDownload(download)) {
                return Promise.reject(download);
            }

            const newDocument = this.productDownloadRepository.create(Context.api);
            newDocument.mediaId = download.id;
            newDocument.download = {
                url: download.url,
                id: download.id
            };

            this.product.extensions.acrisDownloads.add(newDocument);

            return Promise.resolve();
        },

        isExistingDownload(download) {
            return this.product.extensions.acrisDownloads.some(({ id, mediaId }) => {
                return id === download.id || mediaId === download.id;
            });
        },

        saveProduct() {
            if (this.product) {
                this.productRepository.save(this.product, Shopware.Context.api);
            }
        },

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

            const newMedia = this.createDownloadAssociation(mediaItem.id);

            return new Promise((resolve) => {
                this.product.extensions.acrisDownloads.add(newMedia);

                Shopware.State.commit('swProductDetail/setLoading', ['media', false]);

                resolve(newMedia.mediaId);
                return true;
            });
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

        downloadRemoveInheritanceFunction(newValue) {
            newValue.forEach(({ id, mediaId, position }) => {
                const media = this.productDownloadsRepository.create(Shopware.Context.api);
                Object.assign(media, { mediaId, position, productId: this.product.id });

                this.product.extensions.acrisDownloads.push(media);
            });

            this.$refs.productDownloadMediaInheritance.forceInheritanceRemove = true;

            return this.product.extensions.acrisDownloads;
        },

        downloadRestoreInheritanceFunction() {
            this.$refs.productDownloadMediaInheritance.forceInheritanceRemove = false;

            this.product.extensions.acrisDownloads.getIds().forEach((mediaId) => {
                this.product.extensions.acrisDownloads.remove(mediaId);
            });

            return this.product.extensions.acrisDownloads;
        },

        /*removeDownloadMediaItem(state, mediaId) {
            const media = this.product.extensions.acrisDownloads.find((mediaItem) => mediaItem.mediaId === mediaId);

            // remove cover id if mediaId matches
            if (this.product.coverId === media.id) {
                this.product.coverId = null;
            }

            this.product.extensions.acrisDownloads.remove(mediaId);
        },*/

        _checkIfDownloadMediaIsAlreadyUsed(mediaId) {
            return this.product.extensions.acrisDownloads.some((productMedia) => {
                return productMedia.mediaId === mediaId;
            });
        },

        onChange(term) {
            this.product.extensions.acrisLinks.criteria.setPage(1);
            this.product.extensions.acrisLinks.criteria.setTerm(term);

            this.refreshList();
        },

        refreshList() {
            this.$refs.productLinkGrid.load();
        }
    }
});

import template from './acris-edit-download-modal.html.twig';
import './acris-edit-download-modal.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('acris-edit-download-modal', {
    template,

    inject: ['repositoryFactory'],

    props: {
        downloadItem: {
            type: Object,
            required: true
        }
    },

    created() {
        this.createdComponent();
    },

    data() {
        return {
            mediaModalIsOpen: false,
            tabsTotal: 0,
            isLoading: false
        }
    },

    computed: {
        downloadTabRepository() {
            return this.repositoryFactory.create('acris_download_tab');
        },

        uploadTag() {
            return `cms-element-media-config-${this.downloadItem.id}`;
        },

        previewSource() {
            if (this.downloadItem && this.downloadItem.previewMedia && this.downloadItem.previewMediaId) {
                return this.downloadItem.previewMedia;
            }

            return this.downloadItem.previewMediaId;
        },
        productDownloadsEntityName() {
            return 'acris_product_download';
        },
    },

    methods: {
        createdComponent() {
            this.isLoading = true;

            const criteria = new Criteria();
            criteria.setLimit(1);

            this.downloadTabRepository.search(criteria, Shopware.Context.api).then((items) => {
                this.tabsTotal = items.total;
                this.isLoading = false;

                return items;
            }).catch(() => {
                this.isLoading = false;
            });
        },

        changeLanguageSelection(entityCollection) {
            this.downloadItem.languages = entityCollection;
        },

        onCancel() {
            this.$emit('modal-close');
        },

        onApply() {
            this.$emit('modal-save', this.downloadItem);
        },
        onOpenMediaModal() {
            this.mediaModalIsOpen = true;
        },
        onImageRemove() {
            this.downloadItem.previewMediaId = null;
            this.downloadItem.previewMedia = [];

            this.$emit('element-update', this.downloadItem);
        },
        onImageUpload({ targetId }) {
            this.mediaRepository.get(targetId, Shopware.Context.api).then((mediaEntity) => {
                this.downloadItem.previewMediaId = mediaEntity.id;
                this.downloadItem.previewMedia = mediaEntity;

                this.$emit('element-update', this.downloadItem);
            });
        },
        onCloseModal() {
            this.mediaModalIsOpen = false;
        },
        onSelectionChanges(mediaEntity) {
            const media = mediaEntity[0];
            this.downloadItem.previewMediaId = media.id;
            this.downloadItem.previewMedia = media;

            this.$emit('element-update', this.downloadItem);
        },
    }
});

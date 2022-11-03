const {Component, Mixin} = Shopware;
const {Criteria, EntityCollection} = Shopware.Data;

import template from './index.html.twig';
import './index.scss';

Component.register('sw-cms-el-config-moorl-shop-the-look', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    inject: ['repositoryFactory'],

    data() {
        return {
            mediaModalIsOpen: false,
            initialFolderId: null,
            snippetPrefix: 'sw-cms.elements.moorl-shop-the-look.',
        };
    },

    computed: {
        products() {
            if (this.element.data && this.element.data.products && this.element.data.products.length > 0) {
                return this.element.data.products;
            }

            return null;
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        uploadTag() {
            return `cms-element-media-config-${this.element.id}`;
        },

        previewSource() {
            if (this.element.data && this.element.data.media && this.element.data.media.id) {
                return this.element.data.media;
            }

            return this.element.config.media.value;
        },

        mediaUrl() {
            const context = Shopware.Context.api;
            const elemData = this.element.data.media;
            if (elemData && elemData.id) {
                return elemData.url;
            }
            if (elemData && elemData.url) {
                return `${context.assetsPath}${elemData.url}`;
            }
            return `${context.assetsPath}/administration/static/img/cms/preview_mountain_large.jpg`;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('moorl-shop-the-look');
            this.initElementData('moorl-shop-the-look');

            if (typeof this.previewSource === 'string') {
                this.mediaRepository.get(this.previewSource, Shopware.Context.api).then(result => {
                    this.element.data.media = result;
                });
            }
        },

        onProductsChange() {
            const _that = this;

            this.element.config.products.value.forEach(function (id) {
                if (!_that.element.config.productMediaHotspots.value[id]) {
                    _that.element.config.productMediaHotspots.value[id] = {
                        top: 50,
                        left: 50
                    };
                }

                _that.element.config.productMediaHotspots.value[id].top = parseInt(_that.element.config.productMediaHotspots.value[id].top);
                _that.element.config.productMediaHotspots.value[id].left = parseInt(_that.element.config.productMediaHotspots.value[id].left);
            });
        },

        pointerPositionCss(id) {
            try {
                return {
                    top: this.element.config.productMediaHotspots.value[id].top + '%',
                    left: this.element.config.productMediaHotspots.value[id].left + '%'
                }
            } catch (exception) {
                return {};
            }
        },

        async onImageUpload({targetId}) {
            const mediaEntity = await this.mediaRepository.get(targetId, Shopware.Context.api);

            this.element.config.media.value = mediaEntity.id;

            this.updateElementData(mediaEntity);
        },

        onImageRemove() {
            this.element.config.media.value = null;

            this.updateElementData();
        },

        updateElementData(media = null) {
            this.$set(this.element.data, 'mediaId', media === null ? null : media.id);
            this.$set(this.element.data, 'media', media);

            this.$emit('element-update', this.element);
        },

        onCloseModal() {
            this.mediaModalIsOpen = false;
        },

        onSelectionChanges(mediaEntity) {
            const media = mediaEntity[0];
            this.element.config.media.value = media.id;

            this.updateElementData(media);
        },

        onOpenMediaModal() {
            this.mediaModalIsOpen = true;
        }
    }
});

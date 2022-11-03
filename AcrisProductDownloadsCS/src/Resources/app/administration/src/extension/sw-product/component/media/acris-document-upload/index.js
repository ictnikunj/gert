import template from './acris-document-upload.html.twig';
import './acris-document-upload.scss';

const { Criteria } = Shopware.Data;
const { Component, Mixin, Context } = Shopware;
const { fileReader } = Shopware.Utils;

/**
 * @status ready
 * @description The <u>acris-document-upload</u> component is used wherever an upload is needed. It supports drag & drop-,
 * file- and url-upload and comes in various forms.
 * @example-type code-only
 * @component-example
 * <acris-document-upload
 *     uploadTag="my-upload-tag"
 *     variant="regular"
 *     allowMultiSelect="false"
 *     variant="regular"
 *     autoUpload="true"
 *     label="My image-upload">
 * </acris-document-upload>
 */
Component.register('acris-document-upload', {
    template,

    inject: ['repositoryFactory', 'mediaService', 'configService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    props: {
        source: {
            type: [Object, String],
            required: false,
            default: null
        },

        variant: {
            type: String,
            required: false,
            validValues: ['compact', 'regular'],
            validator(value) {
                return ['compact', 'regular'].includes(value);
            },
            default: 'regular'
        },

        uploadTag: {
            type: String,
            required: true
        },

        allowMultiSelect: {
            type: Boolean,
            required: false,
            default: true
        },

        label: {
            type: String,
            required: false
        },

        defaultFolder: {
            type: String,
            required: false,
            validator(value) {
                return value.length > 0;
            },
            default: null
        },

        targetFolderId: {
            type: String,
            required: false,
            default: null
        },

        helpText: {
            type: String,
            required: false,
            default: null
        }
    },

    data() {
        return {
            multiSelect: this.allowMultiSelect,
            showUrlInput: false,
            preview: null,
            isDragActive: false,
            defaultFolderId: null
        };
    },

    computed: {
        mediaRepository() {
            return this.repositoryFactory.create('media');
        },
        defaultFolderRepository() {
            return this.repositoryFactory.create('media_default_folder');
        },

        showPreview() {
            return !this.multiSelect;
        },

        hasPreviewFile() {
            return this.preview !== null;
        },

        toggleButtonCaption() {
            return this.showUrlInput ?
                this.$tc('global.acris-document-upload.buttonSwitchToFileUpload') :
                this.$tc('global.acris-document-upload.buttonSwitchToUrlUpload');
        },

        hasOpenMediaButtonListener() {
            return Object.keys(this.$listeners).includes('media-upload-sidebar-open');
        },

        previewClass() {
            return {
                'has--preview': this.showPreview
            };
        },

        isDragActiveClass() {
            return {
                'is--active': this.isDragActive,
                'is--multi': this.variant === 'regular' && !!this.multiSelect
            };
        },

        mediaFolderId() {
            return this.defaultFolderId || this.targetFolderId;
        }
    },

    watch: {
        async defaultFolder() {
            this.defaultFolderId = await this.getDefaultFolderId();
        }
    },

    created() {
        this.createdComponent();
    },

    mounted() {
        this.mountedComponent();
    },

    beforeDestroy() {
        this.beforeDestroyComponent();
    },

    methods: {
        async createdComponent() {
            this.mediaService.addListener(this.uploadTag, this.handleUploadStoreEvent);
            if (this.mediaFolderId) {
                return;
            }

            if (this.defaultFolder) {
                this.defaultFolderId = await this.getDefaultFolderId();
            }
        },

        mountedComponent() {
            if (this.$refs.dropzone) {
                ['dragover', 'drop'].forEach((event) => {
                    window.addEventListener(event, this.stopEventPropagation, false);
                });
                this.$refs.dropzone.addEventListener('drop', this.onDrop);

                window.addEventListener('dragenter', this.onDragEnter);
                window.addEventListener('dragleave', this.onDragLeave);
            }
        },

        beforeDestroyComponent() {
            this.mediaService.removeByTag(this.uploadTag);
            this.mediaService.removeListener(this.uploadTag, this.handleUploadStoreEvent);

            ['dragover', 'drop'].forEach((event) => {
                window.addEventListener(event, this.stopEventPropagation, false);
            });

            window.removeEventListener('dragenter', this.onDragEnter);
            window.removeEventListener('dragleave', this.onDragLeave);
        },

        /*
         * Drop Handler
         */
        onDrop(event) {
            if (this.disabled) {
                return;
            }

            const newMediaFiles = Array.from(event.dataTransfer.files);
            this.isDragActive = false;

            if (newMediaFiles.length === 0) {
                return;
            }

            this.handleUpload(newMediaFiles);
        },

        onDropDownload(dragData) {
            if (this.disabled) {
                return;
            }

            this.$emit('download-drop', dragData.mediaItem);
        },

        onDragEnter() {
            this.isDragActive = true;
        },

        onDragLeave(event) {
            if (event.screenX === 0 && event.screenY === 0) {
                this.isDragActive = false;
            }
        },

        stopEventPropagation(event) {
            event.preventDefault();
            event.stopPropagation();
        },

        /*
         * Click handler
         */
        onClickUpload() {
            this.$refs.fileInput.click();
        },

        openUrlModal() {
            this.showUrlInput = true;
        },

        closeUrlModal() {
            this.showUrlInput = false;
        },

        toggleShowUrlFields() {
            this.showUrlInput = !this.showUrlInput;
        },

        onClickOpenMediaSidebar() {
            this.$emit('media-upload-sidebar-open');
        },

        onRemoveMediaItem() {
            this.preview = null;
            this.$emit('media-upload-remove-image');
        },

        /*
         * entry points
         */
        async onUrlUpload({ url, fileExtension }) {
            if (!this.multiSelect) {
                this.mediaService.removeByTag(this.uploadTag);
                this.preview = url;
            }

            const fileInfo = fileReader.getNameAndExtensionFromUrl(url);
            if (fileExtension) {
                fileInfo.extension = fileExtension;
            }

            const targetEntity = this.getMediaEntityForUpload();

            await this.mediaRepository.save(targetEntity, Context.api);
            this.mediaService.addUpload(this.uploadTag, { src: url, targetId: targetEntity.id, ...fileInfo });

            this.closeUrlModal();
        },

        onFileInputChange() {
            const newMediaFiles = Array.from(this.$refs.fileInput.files);

            if (newMediaFiles.length) {
                this.handleUpload(newMediaFiles);
            }
            this.$refs.fileForm.reset();
        },

        /*
         * Helper functions
         */
        async handleUpload(newMediaFiles) {
            if (!this.multiSelect) {
                this.mediaService.removeByTag(this.uploadTag);
                newMediaFiles = [newMediaFiles.pop()];
                this.preview = newMediaFiles[0];
            }

            const syncEntities = [];

            const uploadData = newMediaFiles.map((fileHandle) => {
                const { fileName, extension } = fileReader.getNameAndExtensionFromFile(fileHandle);
                const targetEntity = this.getMediaEntityForUpload();
                syncEntities.push(targetEntity);

                return { src: fileHandle, targetId: targetEntity.id, fileName, extension };
            });

            await this.mediaRepository.saveAll(syncEntities, Context.api);
            await this.mediaService.addUploads(this.uploadTag, uploadData);
        },

        getMediaEntityForUpload() {
            const mediaItem = this.mediaRepository.create();
            mediaItem.mediaFolderId = this.mediaFolderId;

            return mediaItem;
        },

        async getDefaultFolderId() {
            const criteria = new Criteria()
                .setLimit(1)
                .addFilter(Criteria.equals('entity', this.defaultFolder));

            const items = await this.defaultFolderRepository.search(criteria, Context.api);
            if (items.length !== 1) {
                return null;
            }
            const defaultFolder = items[0];

            if (defaultFolder.folder && defaultFolder.folder.id) {
                return defaultFolder.folder.id;
            }

            return null;
        },

        handleUploadStoreEvent({ action }) {
            if (action === 'media-upload-fail') {
                this.onRemoveMediaItem();
            }
        }
    }
});

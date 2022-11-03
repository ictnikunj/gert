import template from './acris-product-download-tab-list.html.twig';
import './acris-product-download-tab-list.scss';

const {Component, Mixin} = Shopware;
const { Criteria } = Shopware.Data;

Component.register('acris-product-download-tab-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            items: null,
            isLoading: false,
            showDeleteModal: false,
            repository: null,
            total: 0
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        entityRepository() {
            return this.repositoryFactory.create('acris_download_tab');
        },

        entityCriteria() {
            const criteria = new Criteria(this.page, this.limit);
            criteria.setTerm(this.term);

            return criteria;
        },

        columns() {
            return this.getColumns();
        }
    },

    methods: {
        getList() {
            this.isLoading = true;

            this.entityRepository.search(this.entityCriteria, Shopware.Context.api).then((items) => {
                this.total = items.total;
                this.items = items;
                this.isLoading = false;

                return items;
            }).catch(() => {
                this.isLoading = false;
            });
        },

        onDelete(id) {
            this.showDeleteModal = id;
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },

        getColumns() {
            return [{
                property: 'internalId',
                inlineEdit: 'string',
                label: 'acris-product-download-tab.list.columnInternalId',
                routerLink: 'acris.product.download.tab.detail',
                width: '280px',
                allowResize: true,
                primary: true
            }, {
                property: 'displayName',
                label: 'acris-product-download-tab.list.columnDisplayName',
                routerLink: 'acris.product.download.tab.detail',
                width: '280px',
                allowResize: true,
            }, {
                property: 'priority',
                inlineEdit: 'number',
                label: 'acris-product-download-tab.list.columnPriority',
                routerLink: 'acris.product.download.tab.detail',
                width: '280px',
                allowResize: true,
            }];
        },

        onChangeLanguage(languageId) {
            this.getList(languageId);
        },

        onDuplicate(referenceEntity) {
            this.entityRepository.clone(referenceEntity.id, Shopware.Context.api).then((newEntity) => {
                this.reloadEntity(referenceEntity, newEntity);
            });
        },

        reloadEntity(referenceEntity, newEntity) {
            this.entityRepository
                .get(newEntity.id, Shopware.Context.api, this.entityCriteria)
                .then((entity) => {
                    this.item = entity;
                    this.entityRepository
                        .save(this.item, Shopware.Context.api)
                        .then(() => {
                            this.$router.push(
                                {
                                    name: 'acris.product.download.tab.detail',
                                    params: { id: newEntity.id }
                                }
                            );
                        });
                });
        },
    }
});


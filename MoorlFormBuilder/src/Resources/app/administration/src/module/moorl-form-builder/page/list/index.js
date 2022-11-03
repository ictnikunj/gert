const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

import template from './index.html.twig';

Component.register('moorl-form-builder-list', {
    template,

    inject: [
        'repositoryFactory',
        'numberRangeService'
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing'),
        Mixin.getByName('placeholder')
    ],

    data() {
        return {
            detailRoute: 'moorl.form.builder.detail',
            items: null,
            sortBy: 'name',
            showImportModal: false,
            isLoading: true,
            selectedFile: null,
            isImporting: false,
            locale: null,
            naturalSorting: true,
            sortDirection: 'DESC',
            showDeleteModal: false,
            filterLoading: false,
            filterCriteria: [],
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        repository() {
            return this.repositoryFactory.create('moorl_form');
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        languageRepository() {
            return this.repositoryFactory.create('language');
        },

        columns() {
            return [{
                property: 'active',
                dataIndex: 'active',
                label: this.$t('moorl-form-builder.properties.active'),
                inlineEdit: 'boolean',
                align: 'center',
                width: '50px'
            }, {
                property: 'name',
                dataIndex: 'name',
                label: this.$t('moorl-form-builder.properties.name'),
                routerLink: 'moorl.form.builder.detail',
                inlineEdit: 'string',
                allowResize: true,
                primary: true
            }, {
                property: 'label',
                dataIndex: 'label',
                label: this.$t('moorl-form-builder.properties.label'),
                routerLink: 'moorl.form.builder.detail',
                allowResize: true,
            }, {
                property: 'type',
                dataIndex: 'type',
                label: this.$t('moorl-form-builder.properties.type'),
                allowResize: true,
            }];
        },

        defaultCriteria() {
            const defaultCriteria = new Criteria(this.page, this.limit);
            this.naturalSorting = this.sortBy === 'name';
            defaultCriteria.setTerm(this.term);
            this.sortBy.split(',').forEach(sortBy => {
                defaultCriteria.addSorting(Criteria.sort(sortBy, this.sortDirection, this.naturalSorting));
            });

            this.filterCriteria.forEach(filter => {
                defaultCriteria.addFilter(filter);
            });

            return defaultCriteria;
        },
    },

    created() {
        this.onChangeLanguage();
    },

    methods: {
        onChangeLanguage() {
            const criteria = new Criteria();
            criteria.addAssociation('locale');

            this.languageRepository
                .get(Shopware.Context.api.languageId, Shopware.Context.api, criteria)
                .then((entity) => {
                    this.locale = entity.locale;
                });
        },

        async getFormById(id) {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('id', id));
            let entity = null;
            await this.repository.search(criteria, Shopware.Context.api).then((result) => {
                entity = result.first();
            });
            return entity ? entity : null;
        },

        async getList() {
            this.isLoading = true;

            try {
                const items = await this.repository.search(this.defaultCriteria, Shopware.Context.api);

                this.total = items.total;
                this.tax = items;
                this.isLoading = false;
                this.items = items;
                this.selection = {};
            } catch {
                this.isLoading = false;
            }
        },

        updateSelection() {},

        updateTotal({total}) {
            this.total = total;
        },

        onClickUpload() {
            this.$refs.fileInput.click();
        },

        onFileInputChange() {
            const reader = new FileReader();
            reader.onload = this.onReaderLoad;
            reader.readAsText(this.$refs.fileInput.files[0]);
        },

        async onReaderLoad(event) {
            const form = JSON.parse(event.target.result);
            const localForm = await this.getFormById(form.id);

            if (localForm) {
                Object.assign(localForm, form);

                this.repository.save(localForm, Shopware.Context.api).then(() => {
                    console.log("form updated");
                    this.getList();
                });
            } else {
                const newItem = this.repository.create(Shopware.Context.api);

                form.id = newItem.id;
                Object.assign(newItem, form);

                this.repository.save(newItem, Shopware.Context.api).then(() => {
                    console.log("form created");
                    this.getList();
                });
            }

            console.log(form);
        },

        onClickDownload(form) {
            const json = JSON.stringify(form);
            let a = document.createElement('a');
            a.href = 'data:attachment/json,' + encodeURIComponent(json);
            a.target = '_blank';
            a.download = form.name + '.json';
            document.body.appendChild(a);
            a.click();
        },

        onDuplicate(reference) {
            this.repository.clone(reference.id, Shopware.Context.api, {
                name: `${reference.name} ${this.$tc('sw-product.general.copy')}`,
                createdAt: null,
                locked: false
            }).then((duplicate) => {
                this.$router.push({name: 'moorl.form.builder.detail', params: {id: duplicate.id}});
            });
        }
    }
});

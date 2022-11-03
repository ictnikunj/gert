import template from './media-redirect-list.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('media-redirect-list', {
    template,

    inject: [
        'repositoryFactory'
    ],

    data() {
        return {
            repository: null,
            mediaredirect: null
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        columns() {
            return [
                {
                    property: 'url',
                    dataIndex: 'url',
                    label: 'URL',
                    routerLink: 'media.redirect.detail',
                    allowResize: true,
                    primary: true
            },
                {
                    property: 'mediaData',
                    label: 'Images',
                    allowResize: true,
                    primary: true
            }
            ];
        }
    },

    created() {
        this.repository = this.repositoryFactory.create('ict_media_redirect');

        this.getmediaRedirectList();
    },

    methods: {
        getmediaRedirectList() {
            const criteria = new Criteria();
            criteria.getAssociation('media');
            this.repository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.mediaredirect = result;
                });
        },
    }
});

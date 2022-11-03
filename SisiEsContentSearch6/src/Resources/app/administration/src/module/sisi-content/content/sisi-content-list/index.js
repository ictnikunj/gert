import template from './sisi-content-list.html.twig';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('sisi-content-list', {
    template,

    inject: [
        'repositoryFactory'
    ],

    data() {
        return {
            fields: null,
            isLoading: true,
            sortBy: 'createdAt',
            sortDirection: 'DESC'
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },
    methods: {

    },

    computed: {
        teaserColumns() {
            return [
                {
                    property: 'label',
                    dataIndex: 'label',
                    label: this.$t('sisi-content.list.label'),
                    inlineEdit: 'string',
                    allowResize: true,
                    primary: true
                },
                {
                    property: 'shop',
                    dataIndex: 'shop',
                    label: this.$t('sisi-content.list.shop'),
                    inlineEdit: 'string',
                    allowResize: true,
                    primary: true
                }
            ]
        },
    },

    created() {
        this.repository = this.repositoryFactory.create('sisi_escontent_fields');
        this.repository
            .search(new Criteria(), Shopware.Context.api)
            .then((result) => {
                this.fields = result;
            });
    }
});

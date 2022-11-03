import template from './sisi-content-detail.html.twig';

const {Component, Mixin} = Shopware;
const httpClient = Shopware.Application.getContainer('init').httpClient;

Component.register('sisi-content-detail', {
    template,

    inject: [
        'repositoryFactory',
        'SisiApiCredentialsService'
    ],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },


    data() {

        return {
            fields: null,
            isLoading: false,
            processSuccess: false,
            shop: '',
            options: [],
            optininsstring: '',
            optionsLanguage: [],
            language: '',
            tokenizer: '',
            edge: 'none',
            stemmingStr: 'none',
            colorName: 'white',
            colorshop: 'white',
            strEdge: 'block'

        };
    },
    computed: {

    },
    created() {
        this.repository = this.repositoryFactory.create('sisi_escontent_fields');
        this.getBundle();
        this.getChannels();
        this.choiceFields();
    },
    methods: {
        getBundle() {
            this.repository
                .get(this.$route.params.id, Shopware.Context.api)
                .then((entity) => {
                    this.fields = entity;
                    this.choiceFields();
                });

        },
        onClickSave() {
            this.validate()
            if (this.strvalidate) {
                this.isLoading = true;
                this.repository
                    .save(this.fields, Shopware.Context.api)
                    .then(() => {
                        this.getBundle();
                        this.isLoading = false;
                        this.processSuccess = true;
                    }).catch((exception) => {
                      this.isLoading = false;
                });
            }
        },
        saveFinish() {
            this.processSuccess = false;
        },
        choiceFields() {

            if (this.fields.tokenizer === 'Edge_n-gram_tokenizer' || this.fields.tokenizer === 'ngram' || this.fields.tokenizer === 'edge_ngram' ||
                this.fields.filter1 === 'autocomplete' || this.fields.filter2  === 'autocomplete' || this.fields.filter3  === 'autocomplete') {
                this.edge = 'block';
            } else {
                this.edge = 'none';
            }


            if (this.fields.filter1 === 'autocomplete' || this.fields.filter2  === 'autocomplete' || this.fields.filter3  === 'autocomplete') {
                this.strEdge ='none';
            } else {
                this.strEdge ='block';
            }


            if (this.fields.tokenizer === 'simple_pattern') {
                this.pattern = 'block';
            } else {
                this.pattern = 'none';
            }

        },

        changeStemming() {

            if (this.fields.stemming !== 'noselect') {
                this.stemmingStr = 'block';
            } else {
                this.stemmingStr = 'none';
            }
        },
        validate() {

            this.strvalidate = true;
            if (this.fields.tokenizer === "" || this.fields.tokenizer == null) {
                this.strvalidate = false;
                this.colorName = 'red';
            } else {
                this.colorName = 'white';
            }

            if (this.fields.shop === "" || this.fields.shop == null) {
                this.strvalidate = false;
                this.colorshop = 'red';
            } else {
                this.colorshop = 'white';
            }
        },
        getChannels() {
            this.SisiApiCredentialsService.channels().then((response) => {
                this.options = response['channel'];
                this.optionsLanguage = response['language'];
                this.optionsLanguage.shift();
            }).catch((exception) => {

            });

        }
    }

});

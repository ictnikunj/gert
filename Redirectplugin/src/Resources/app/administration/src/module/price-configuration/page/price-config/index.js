import template from './price-config.html.twig';

const { Component, Defaults  } = Shopware;
const { Criteria } = Shopware.Data;
const { hasOwnProperty } = Shopware.Utils.object;

Component.register('price-config', {
    template,

    inject: [
        'repositoryFactory',
        'acl',
        'configService'
    ],

    mixins: [
        'notification'
    ],

    data(){
        return {
            config: null,
            languageid:null
        }
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },
    },

    watch: {
        config: {
            handler() {
                const defaultConfig = this.$refs.configComponent.allConfigs.null;
                const salesChannelId = this.$refs.configComponent.selectedSalesChannelId;
            },
            deep: true
        }
    },
    methods:{
        onImport(){
            let headers = this.configService.getBasicHeaders();
            let data = new FormData();
            data.append('languageid',this.languageid);
            this.configService.httpClient.post('/url/initialimport',data,{headers}).then((response) => {
                this.createNotificationSuccess({
                    title: this.$tc('priceconfiguration.titleSaveSuccess'),
                    message: this.$tc('priceconfiguration.messageSaveSuccess')
                });
            });
        }
    }
});

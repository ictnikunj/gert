import template from './pim-import-list.html.twig';

const { Component,Mixin } = Shopware;

import deDE from './../../snippet/de-DE.json';
import enGB from './../../snippet/en-GB.json';

const { Criteria } = Shopware.Data;

Component.register('pim-import-list', {
    template,

    inject: [
        'configService',
        'systemConfigApiService'
    ],
    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            counter: null,
            vrelatedProductCounter:null,
            vproductPartCounter:null,
            vaddonProductCounter:null,
            vcategory_counter:null,
            PimImportSetting:{
                'PimImport.config.mainProductCounter' : null,
                'PimImport.config.relatedProductCounter' : null,
                'PimImport.config.productPartCounter' : null,
                'PimImport.config.addonProductCounter' : null,
                'PimImport.config.category_counter' : null,
                'PimImport.config.CategoryPublicationCode' : null,
            },
            mainProductCounter:null,
            relatedProductCounter:null,
            productPartCounter:null,
            addonProductCounter:null,
            category_counter:null,
            CategoryPublicationCode:null,
            currentValue:null,
            pim_property_counter:null,
            pim_product_property_counter:null,
            pim_category_cron_counter: null,
        };
    },

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    created() {
        this.createdComponent()
    },

    methods: {

        async createdComponent(){
            this.PimImportSetting = await this.systemConfigApiService.getValues('PimImport');
            this.mainProductCounter = this.PimImportSetting['PimImport.config.mainProductCounter'];
            this.relatedProductCounter = this.PimImportSetting['PimImport.config.relatedProductCounter'];
            this.productPartCounter = this.PimImportSetting['PimImport.config.productPartCounter'];
            this.addonProductCounter = this.PimImportSetting['PimImport.config.addonProductCounter'];
            this.category_counter = this.PimImportSetting['PimImport.config.category_counter'];
            this.CategoryPublicationCode = this.PimImportSetting['PimImport.config.CategoryPublicationCode'];
        },

        onSave(){
            let headers = this.configService.getBasicHeaders();

            return this.configService.httpClient
                .get(
                    '/pim/productimport',
                    {
                        params:{counter:document.getElementById("pim_main_p_counter").textContent},
                        headers
                    }
                ).then((response) => {
                    let loopcounter = response.data.counter;
                    let endcounter = response.data.endcounter;

                    if (response.data.type == 'error') {
                        this.createNotificationError({
                            title: response.data.type,
                            message: response.data.message
                        });
                        return;
                    }
                    this.createNotificationSuccess({
                        title: response.data.type,
                        message: response.data.message
                    });
            if (loopcounter) {
                document.getElementById("pim_main_p_counter").innerHTML = loopcounter;
                this.counter = (loopcounter-1)+'/'+(endcounter-1);
                if (loopcounter == endcounter || loopcounter > endcounter) {
                    //stop import product
                } else {
                    this.$refs.pimMainButton.$el.click();
                }
            }
                });
        },

        onCrosssellingRelated(){

            let headers = this.configService.getBasicHeaders();

            return this.configService.httpClient
                .get(
                    '/pim/productcrossselling',
                    {
                        params:{counter:document.getElementById("pim_related_counter").textContent},
                        headers
                    }
                ).then((response) => {
                    let loopcounter = response.data.counter;
                    let endcounter = response.data.endcounter;

                    if (response.data.type == 'error') {
                        this.createNotificationError({
                            title: response.data.type,
                            message: response.data.message
                        });
                        return;
                    }
                    this.createNotificationSuccess({
                        title: response.data.type,
                        message: response.data.message
                    });

            if (loopcounter) {
                document.getElementById("pim_related_counter").innerHTML = loopcounter;
                this.vrelatedProductCounter = (loopcounter-1)+'/'+(endcounter-1);
                if (loopcounter == endcounter || loopcounter > endcounter) {
                    //stop import product
                } else {
                    this.$refs.pimRelatedButton.$el.click();
                }
            }
                });
        },

        onCrosssellingProductPart(){

            let headers = this.configService.getBasicHeaders();

            return this.configService.httpClient
                .get(
                    '/pim/productcrosssellingproductpart',
                    {
                        params:{counter:document.getElementById("pim_product_part_counter").textContent},
                        headers
                    }
                ).then((response) => {

                    let loopcounter = response.data.counter;
                    let endcounter = response.data.endcounter;

                    if (response.data.type == 'error') {
                        this.createNotificationError({
                            title: response.data.type,
                            message: response.data.message
                        });
                        return;
                    }
                    this.createNotificationSuccess({
                        title: response.data.type,
                        message: response.data.message
                    });

            if (loopcounter) {
                document.getElementById("pim_product_part_counter").innerHTML = loopcounter;
                this.vproductPartCounter = (loopcounter-1)+'/'+(endcounter-1);
                if (loopcounter == endcounter || loopcounter > endcounter) {
                    //stop import product
                } else {
                    this.$refs.pimProductPartButton.$el.click();
                }
            }

                });
        },

        onCrosssellingAddon(){

            let headers = this.configService.getBasicHeaders();

            return this.configService.httpClient
                .get(
                    '/pim/productcrosssellingaddon',
                    {
                        params:{counter:document.getElementById("pim_addon_counter").textContent},
                        headers
                    }
                ).then((response) => {

                    let loopcounter = response.data.counter;
                    let endcounter = response.data.endcounter;

                    if (response.data.type == 'error') {
                        this.createNotificationError({
                            title: response.data.type,
                            message: response.data.message
                        });
                        return;
                    }
                    this.createNotificationSuccess({
                        title: response.data.type,
                        message: response.data.message
                    });

            if (loopcounter) {
                document.getElementById("pim_addon_counter").innerHTML = loopcounter;
                this.vaddonProductCounter = (loopcounter-1)+'/'+(endcounter-1);
                if (loopcounter == endcounter || loopcounter > endcounter) {
                    //stop import product
                } else {
                    this.$refs.pimAddonButton.$el.click();
                }
            }

                });
        },

         async onCategory(){
                let headers = this.configService.getBasicHeaders();

                this.CategoryPublicationCode = await this.systemConfigApiService.getValues('PimImport');

                return this.configService.httpClient
                .get(
                    '/pim/categoryimport',
                    {
                        params:{
                            counter:document.getElementById("pim_category_counter").textContent,
                            salesChannelId:this.currentValue
                        },
                        headers
                    }
                ).then(( response) =>{

                    let loopcounter = response.data.counter;
                    let endcounter = response.data.endcounter;

                    let CategoryPublicationCode = response.data.CategoryPublicationCode;
                    let currentPublicationCode = response.data.currentPublicationCode;

                    let DBPublicationCode = this.CategoryPublicationCode['PimImport.config.CategoryPublicationCode'];

                    if (response.data.type == 'error') {
                        this.createNotificationError({
                            title: response.data.type,
                            message: response.data.message
                        });
                        return;
                    } else {
                        this.createNotificationSuccess({
                            title: response.data.type,
                            message: response.data.message
                        });
                    }

                    if (loopcounter) {
                        document.getElementById("pim_category_counter").innerHTML = loopcounter;
                        this.vcategory_counter = (loopcounter-1)+'/'+(endcounter-1) +' '+DBPublicationCode;

                        if (loopcounter == endcounter || loopcounter > endcounter) {
                            //loop stop
                        } else {
                            this.$refs.pimCategoryButton.$el.click();
                        }
                    }
                });
        },

        async onCategoryOrder(){
            let headers = this.configService.getBasicHeaders();
            this.CategoryPublicationCode = await this.systemConfigApiService.getValues('PimImport');

            return this.configService.httpClient
                .get(
                    '/pim/categoryorderimport',
                    {
                        params:{
                            counter:document.getElementById("pim_category_counter").textContent,
                            salesChannelId:this.currentValue
                        },
                        headers
                    }
                ).then(( response) =>{

                    let loopcounter = response.data.counter;
                    let endcounter = response.data.endcounter;

                    let CategoryPublicationCode = response.data.CategoryPublicationCode;
                    let currentPublicationCode = response.data.currentPublicationCode;

                    let DBPublicationCode = this.CategoryPublicationCode['PimImport.config.CategoryPublicationCode'];

                    if (response.data.type == 'error') {
                        this.createNotificationError({
                            title: response.data.type,
                            message: response.data.message
                        });
                        return;
                    } else {
                        this.createNotificationSuccess({
                            title: response.data.type,
                            message: response.data.message
                        });
                    }

                    if (loopcounter) {
                        document.getElementById("pim_category_counter").innerHTML = loopcounter;
                        this.vcategory_counter = (loopcounter-1)+'/'+(endcounter-1) +' '+DBPublicationCode;

                        if (loopcounter == endcounter || loopcounter > endcounter) {
                            //loop stop
                        } else {
                            this.$refs.pimCategoryOrderButton.$el.click();
                        }
                    }
                });
        },
        async onManageCron() {
            let headers = this.configService.getBasicHeaders();
            this.CategoryPublicationCode = await this.systemConfigApiService.getValues('PimImport');
            return this.configService.httpClient.get(
                '/pim/manuallycronmanage',
                {
                    params:{
                        salesChannelId:this.currentValue
                    },headers
                }
            ).then(( response) =>{
                if (response.data.type === 'error') {
                    this.createNotificationError({
                        title: response.data.type,
                        message: response.data.message
                        });
                } else {
                    this.createNotificationSuccess({
                        title: response.data.type,
                        message: response.data.message
                        });
                }
            });
        },
        async onDeleteCategory() {
            let headers = this.configService.getBasicHeaders();

            this.CategoryPublicationCode = await this.systemConfigApiService.getValues('PimImport');

            return this.configService.httpClient
                .get(
                    '/pim/categorydelete',
                    {
                        params:{
                            salesChannelId:this.currentValue
                        },
                        headers
                    }
                ).then(( response) =>{

                    let CategoryPublicationCode = response.data.CategoryPublicationCode;
                    let currentPublicationCode = response.data.currentPublicationCode;

                    let DBPublicationCode = this.CategoryPublicationCode['PimImport.config.CategoryPublicationCode'];

                    if (response.data.type == 'error') {
                        this.createNotificationError({
                            title: response.data.type,
                            message: response.data.message
                        });
                        return;
                    } else {
                        this.createNotificationSuccess({
                            title: response.data.type,
                            message: response.data.message
                        });
                    }
                });
        },
        async onProperty(){
            let headers = this.configService.getBasicHeaders();
            this.PimSettings = await this.systemConfigApiService.getValues('PimImport');

            return this.configService.httpClient
                .get(
                    '/pim/propertyImport',
                    {
                        params:{
                            counter:document.getElementById("pim_property_counter").textContent
                        },
                        headers
                    }
                ).then((response) =>{
                    let loopCounter = response.data.counter;
                    let endCounter = response.data.endCounter;

                    if (response.data.type === 'error') {
                        this.createNotificationError({
                            title: response.data.type,
                            message: response.data.message
                        });
                        return;
                    } else {
                        this.createNotificationSuccess({
                            title: response.data.type,
                            message: response.data.message
                        });
                    }

                    if (loopCounter) {
                        document.getElementById("pim_property_counter").innerHTML = loopCounter;
                        this.pim_property_counter = (loopCounter-1)+'/'+(endCounter-1);

                        if (loopCounter === endCounter || loopCounter > endCounter) {
                            //loop stop
                        } else {
                            this.$refs.pimPropertyButton.$el.click();
                        }
                    }
                });
        },

        async onProductProperty(){
            let headers = this.configService.getBasicHeaders();
            this.PimSettings = await this.systemConfigApiService.getValues('PimImport');

            return this.configService.httpClient
                .get(
                    '/pim/ProductPropertyImport',
                    {
                        params:{
                            counter:document.getElementById("pim_product_property_counter").textContent
                        },
                        headers
                    }
                ).then((response) =>{
                    let loopCounter = response.data.counter;
                    let endCounter = response.data.endCounter;

                    if (response.data.type === 'error') {
                        this.createNotificationError({
                            title: response.data.type,
                            message: response.data.message
                        });
                        return;
                    } else {
                        this.createNotificationSuccess({
                            title: response.data.type,
                            message: response.data.message
                        });
                    }

                    if (loopCounter) {
                        document.getElementById("pim_product_property_counter").innerHTML = loopCounter;
                        this.pim_product_property_counter = (loopCounter-1)+'/'+(endCounter-1);

                        if (loopCounter === endCounter || loopCounter > endCounter) {
                            //loop stop
                        } else {
                            this.$refs.pimProductPropertyButton.$el.click();
                        }
                    }
                });
        },

        async onCategoryCron() {
            console.log("Hello Cron");
            let headers = this.configService.getBasicHeaders();

            this.CategoryPublicationCode = await this.systemConfigApiService.getValues('PimImport');

            return this.configService.httpClient
                .get(
                    '/pim/categorycronimport',
                    {

                        params: {
                            counter:document.getElementById("pim_category_cron_button").textContent,
                            salesChannelId: this.currentValue
                        },
                        headers
                    }
                ).then((response) => {

                    let loopcounter = response.data.counter;
                    let endcounter = response.data.endcounter;

                    let CategoryPublicationCode = response.data.CategoryPublicationCode;
                    let currentPublicationCode = response.data.currentPublicationCode;

                    let DBPublicationCode = this.CategoryPublicationCode['PimImport.config.CategoryPublicationCode'];

                    if (response.data.type == 'error') {
                        this.createNotificationError({
                            title: response.data.type,
                            message: response.data.message
                        });
                        return;
                    } else {
                        this.createNotificationSuccess({
                            title: response.data.type,
                            message: response.data.message
                        });
                    }

                    if (loopcounter) {
                        document.getElementById("pim_category_cron_counter").innerHTML = loopcounter;
                        this.vcategory_counter = (loopcounter - 1) + '/' + (endcounter - 1) + ' ' + DBPublicationCode;

                        if (loopcounter == endcounter || loopcounter > endcounter) {
                            //loop stop
                        } else {
                            this.$refs.pimCategoryCronButton.$el.click();
                        }
                    }
                });
        },
    }

});

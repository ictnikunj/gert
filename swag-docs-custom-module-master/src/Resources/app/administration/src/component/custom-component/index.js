import template from './script-button.html.twig';
const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('script-button', {
    template,
    inject: [
        'pluginService'
    ],
    methods: {
    	buttonNewClicked: function () {
    		let headers = this.pluginService.getBasicHeaders();
    		headers = {
                        ...headers,
                        'Content-Type' : 'multipart/form-data'
            }
    		return this.pluginService.httpClient
                .get('/script/import',{headers})
                .then((response) => {
                	     
                }).catch(error => {

                });
    	},
        buttonAttachmentClicked: function () {
            let headers = this.pluginService.getBasicHeaders();
            headers = {
                        ...headers,
                        'Content-Type' : 'multipart/form-data'
            }
            return this.pluginService.httpClient
                .get('/script/attachment',{headers})
                .then((response) => {
                         
                }).catch(error => {

                });
        }
    }
});

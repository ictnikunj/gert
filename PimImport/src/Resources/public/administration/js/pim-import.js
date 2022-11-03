!function(t){var e={};function r(n){if(e[n])return e[n].exports;var o=e[n]={i:n,l:!1,exports:{}};return t[n].call(o.exports,o,o.exports,r),o.l=!0,o.exports}r.m=t,r.c=e,r.d=function(t,e,n){r.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:n})},r.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},r.t=function(t,e){if(1&e&&(t=r(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var n=Object.create(null);if(r.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var o in t)r.d(n,o,function(e){return t[e]}.bind(null,o));return n},r.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return r.d(e,"a",e),e},r.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},r.p="/bundles/pimimport/",r(r.s="kPek")}({"6i3y":function(t){t.exports=JSON.parse('{"pim-import":{"general":{"mainMenuItemGeneral":"PIM Import"},"label":{"title":"PIM Import Configuration","btn":"Main Product Import","crossRlatedItemsbtn":"Related Product Import","crossProductPartsbtn":"Product Part Import","crossAddonbtn":"Addon Import","categoryButton":"Category Import","categoryOrderButton":"Category Manage Order","PropertyButton":"Property Import","ProductPropertyButton":"Product Property Import","deleteCategory":"Category Delete","CategoryCronButton":"Cron Button"}}}')},GtFt:function(t){t.exports=JSON.parse('{"pim-import":{"general":{"mainMenuItemGeneral":"PIM Import"},"label":{"title":"PIM Import Configuration","btn":"Main Product Import","crossRlatedItemsbtn":"Related Product Import","crossProductPartsbtn":"Product Part Import","crossAddonbtn":"Addon Import","categoryButton":"Category Import","categoryOrderButton":"Category Manage Order","PropertyButton":"Property Import","ProductPropertyButton":"Product Property Import","deleteCategory":"Category Delete"}}}')},kPek:function(t,e,r){"use strict";r.r(e);var n=r("GtFt"),o=r("6i3y");function a(t,e,r,n,o,a,i){try{var c=t[a](i),u=c.value}catch(t){return void r(t)}c.done?e(u):Promise.resolve(u).then(n,o)}function i(t){return function(){var e=this,r=arguments;return new Promise((function(n,o){var i=t.apply(e,r);function c(t){a(i,n,o,c,u,"next",t)}function u(t){a(i,n,o,c,u,"throw",t)}c(void 0)}))}}var c=Shopware,u=c.Component,s=c.Mixin;Shopware.Data.Criteria;u.register("pim-import-list",{template:'{% block pim_import_list %}\n    <sw-page class="pim-import-list">\n        <template slot="content">\n            <sw-card class="sw-settings-shipping-detail__condition_container">\n                <div class="collection-container">\n\n                    <div style="width:100%;"><h1>{{ $t(\'pim-import.label.title\') }}</h1></div>\n\n                    <sw-button ref="pimMainButton" id="pim_main_button" :disabled="false" variant="primary"\n                               :square="false" :block="false" :isLoading="false"\n                               @click="onSave">{{ $t(\'pim-import.label.btn\') }}</sw-button>\n                    <p id="pim_main_p_counter"\n                       style="display:none">{{ PimImportSetting[\'PimImport.config.mainProductCounter\'] }}</p>\n                    <p v-if="counter" id="pim_main_status">{{ counter }}{{ \' import product successfully.\' }}</p>\n\n                    <br>\n                    <br>\n\n                    <h2>Cross Selling</h2>\n\n                    <sw-button ref="pimRelatedButton" id="pim_related_button" :disabled="false" variant="primary"\n                               :square="false" :block="false" :isLoading="false"\n                               @click="onCrosssellingRelated">{{ $t(\'pim-import.label.crossRlatedItemsbtn\') }}</sw-button>\n\n                    <sw-button ref="pimProductPartButton" id="pim_product_part_button" :disabled="false"\n                               variant="primary" :square="false" :block="false" :isLoading="false"\n                               @click="onCrosssellingProductPart">{{ $t(\'pim-import.label.crossProductPartsbtn\') }}</sw-button>\n\n                    <sw-button ref="pimAddonButton" id="pim_addon_button" :disabled="false" variant="primary"\n                               :square="false" :block="false" :isLoading="false"\n                               @click="onCrosssellingAddon">{{ $t(\'pim-import.label.crossAddonbtn\') }}</sw-button>\n\n                    <br>\n                    <br>\n\n                    <div>\n                        <p id="pim_related_counter"\n                           style="display:none">{{ PimImportSetting[\'PimImport.config.relatedProductCounter\'] }}</p>\n                        <p v-if="vrelatedProductCounter"\n                           id="pim_related_status">{{ vrelatedProductCounter }}{{ \' import related product in cross selling successfully.\' }}</p>\n\n                        <p id="pim_product_part_counter"\n                           style="display:none">{{ PimImportSetting[\'PimImport.config.productPartCounter\'] }}</p>\n                        <p v-if="vproductPartCounter"\n                           id="pim_product_part_status">{{ vproductPartCounter }}{{ \' import subproduct in cross selling successfully.\' }}</p>\n\n                        <p id="pim_addon_counter"\n                           style="display:none">{{ PimImportSetting[\'PimImport.config.addonProductCounter\'] }}</p>\n                        <p v-if="vaddonProductCounter"\n                           id="pim_addon_status">{{ vaddonProductCounter }}{{ \' import addon product in cross selling successfully.\' }}</p>\n                    </div>\n\n                    <br>\n\n                    <h2>Category Import</h2>\n                    <sw-entity-single-select v-model="currentValue"\n                                             :label="$tc(\'sw-newsletter-recipient.general.salesChannel\')"\n                                             labelProperty="name"\n                                             entity="sales_channel">\n                    </sw-entity-single-select>\n                    <sw-button ref="pimCategoryButton" id="pim_category_button" :disabled="false" variant="primary"\n                               :square="false" :block="false" :isLoading="false"\n                               @click="onCategory">{{ $t(\'pim-import.label.categoryButton\') }}</sw-button>\n                    <sw-button ref="pimCategoryOrderButton" id="pim_category_order_button" :disabled="false"\n                               variant="primary" :square="false" :block="false" :isLoading="false"\n                               @click="onCategoryOrder">\n                        {{ $t(\'pim-import.label.categoryOrderButton\') }}\n                    </sw-button>\n                    <sw-button ref="pimCategoryDeleteButton"\n                               id="pim_category_delete_button"\n                               :disabled="false"\n                               variant="primary"\n                               :square="false"\n                               :block="false"\n                               :isLoading="false"\n                               @click="onDeleteCategory">\n                        {{ $t(\'pim-import.label.deleteCategory\') }}\n                    </sw-button>\n\n                    <p id="pim_category_counter"\n                       style="display:none">{{ PimImportSetting[\'PimImport.config.CategoryCounter\'] }}</p>\n                    <p v-if="vcategory_counter">{{ vcategory_counter }}{{ \' import category successfully.\' }}</p>\n\n                    <sw-button ref="pimManualRunCronButton"\n                               id="pim_manual_run_cron_button"\n                               :disabled="false"\n                               variant="primary"\n                               :square="false"\n                               :block="false"\n                               :isLoading="false"\n                               @click="onManageCron">\n                        Manual Cron Run\n                    </sw-button>\n                    <br><br>\n\n                    <h2>Properties Import</h2>\n                    <sw-button ref="pimPropertyButton"\n                               id="pim_property_button"\n                               :disabled="false"\n                               variant="primary"\n                               :square="false"\n                               :block="false"\n                               :isLoading="false"\n                               @click="onProperty">\n                        {{ $t(\'pim-import.label.PropertyButton\') }}\n                    </sw-button>\n                    <p id="pim_property_counter"\n                       style="display:none">{{ PimImportSetting[\'PimImport.config.PropertyCounter\'] }}</p>\n                    <p v-if="pim_property_counter">{{ pim_property_counter }}{{ \' Import Property Successfully.\' }}</p>\n\n                    <br><br>\n\n                    <h2>Product Properties Import</h2>\n                    <sw-button ref="pimProductPropertyButton"\n                               id="pim_product_property_button"\n                               :disabled="false"\n                               variant="primary"\n                               :square="false"\n                               :block="false"\n                               :isLoading="false"\n                               @click="onProductProperty">\n                        {{ $t(\'pim-import.label.ProductPropertyButton\') }}\n                    </sw-button>\n                    <p id="pim_product_property_counter"\n                       style="display:none">{{ PimImportSetting[\'PimImport.config.ProductPropertyCounter\'] }}</p>\n                    <p v-if="pim_product_property_counter">{{ pim_product_property_counter }}{{ \' Import Product Property Successfully.\' }}</p>\n\n                </div>\n\n                <div>\n                    <h2>Category Cron Button</h2>\n                    <sw-button ref="pimCategoryCronButton"\n                               id="pim_category_cron_button"\n                               :disabled="false"\n                               variant="primary"\n                               :square="false"\n                               :block="false"\n                               :isLoading="false"\n                               @click="onCategoryCron">{{ $t(\'pim-import.label.CategoryCronButton\') }}</sw-button>\n\n                    <p id="pim_category_cron_counter"\n                       style="display:none">{{ PimImportSetting[\'PimImport.config.CategoryCronCounter\'] }}</p>\n                    <p v-if="pim_category_cron_counter">{{ pim_category_cron_counter }}{{ \' Import Category Cron Successfully.\' }}</p>\n\n                </div>\n            </sw-card>\n        </template>\n    </sw-page>\n{% endblock %}\n',inject:["configService","systemConfigApiService"],mixins:[s.getByName("notification")],data:function(){return{counter:null,vrelatedProductCounter:null,vproductPartCounter:null,vaddonProductCounter:null,vcategory_counter:null,PimImportSetting:{"PimImport.config.mainProductCounter":null,"PimImport.config.relatedProductCounter":null,"PimImport.config.productPartCounter":null,"PimImport.config.addonProductCounter":null,"PimImport.config.category_counter":null,"PimImport.config.CategoryPublicationCode":null},mainProductCounter:null,relatedProductCounter:null,productPartCounter:null,addonProductCounter:null,category_counter:null,CategoryPublicationCode:null,currentValue:null,pim_property_counter:null,pim_product_property_counter:null,pim_category_cron_counter:null}},snippets:{"de-DE":n,"en-GB":o},created:function(){this.createdComponent()},methods:{createdComponent:function(){var t=this;return i(regeneratorRuntime.mark((function e(){return regeneratorRuntime.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return e.next=2,t.systemConfigApiService.getValues("PimImport");case 2:t.PimImportSetting=e.sent,t.mainProductCounter=t.PimImportSetting["PimImport.config.mainProductCounter"],t.relatedProductCounter=t.PimImportSetting["PimImport.config.relatedProductCounter"],t.productPartCounter=t.PimImportSetting["PimImport.config.productPartCounter"],t.addonProductCounter=t.PimImportSetting["PimImport.config.addonProductCounter"],t.category_counter=t.PimImportSetting["PimImport.config.category_counter"],t.CategoryPublicationCode=t.PimImportSetting["PimImport.config.CategoryPublicationCode"];case 9:case"end":return e.stop()}}),e)})))()},onSave:function(){var t=this,e=this.configService.getBasicHeaders();return this.configService.httpClient.get("/pim/productimport",{params:{counter:document.getElementById("pim_main_p_counter").textContent},headers:e}).then((function(e){var r=e.data.counter,n=e.data.endcounter;"error"!=e.data.type?(t.createNotificationSuccess({title:e.data.type,message:e.data.message}),r&&(document.getElementById("pim_main_p_counter").innerHTML=r,t.counter=r-1+"/"+(n-1),r==n||r>n||t.$refs.pimMainButton.$el.click())):t.createNotificationError({title:e.data.type,message:e.data.message})}))},onCrosssellingRelated:function(){var t=this,e=this.configService.getBasicHeaders();return this.configService.httpClient.get("/pim/productcrossselling",{params:{counter:document.getElementById("pim_related_counter").textContent},headers:e}).then((function(e){var r=e.data.counter,n=e.data.endcounter;"error"!=e.data.type?(t.createNotificationSuccess({title:e.data.type,message:e.data.message}),r&&(document.getElementById("pim_related_counter").innerHTML=r,t.vrelatedProductCounter=r-1+"/"+(n-1),r==n||r>n||t.$refs.pimRelatedButton.$el.click())):t.createNotificationError({title:e.data.type,message:e.data.message})}))},onCrosssellingProductPart:function(){var t=this,e=this.configService.getBasicHeaders();return this.configService.httpClient.get("/pim/productcrosssellingproductpart",{params:{counter:document.getElementById("pim_product_part_counter").textContent},headers:e}).then((function(e){var r=e.data.counter,n=e.data.endcounter;"error"!=e.data.type?(t.createNotificationSuccess({title:e.data.type,message:e.data.message}),r&&(document.getElementById("pim_product_part_counter").innerHTML=r,t.vproductPartCounter=r-1+"/"+(n-1),r==n||r>n||t.$refs.pimProductPartButton.$el.click())):t.createNotificationError({title:e.data.type,message:e.data.message})}))},onCrosssellingAddon:function(){var t=this,e=this.configService.getBasicHeaders();return this.configService.httpClient.get("/pim/productcrosssellingaddon",{params:{counter:document.getElementById("pim_addon_counter").textContent},headers:e}).then((function(e){var r=e.data.counter,n=e.data.endcounter;"error"!=e.data.type?(t.createNotificationSuccess({title:e.data.type,message:e.data.message}),r&&(document.getElementById("pim_addon_counter").innerHTML=r,t.vaddonProductCounter=r-1+"/"+(n-1),r==n||r>n||t.$refs.pimAddonButton.$el.click())):t.createNotificationError({title:e.data.type,message:e.data.message})}))},onCategory:function(){var t=this;return i(regeneratorRuntime.mark((function e(){var r;return regeneratorRuntime.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return r=t.configService.getBasicHeaders(),e.next=3,t.systemConfigApiService.getValues("PimImport");case 3:return t.CategoryPublicationCode=e.sent,e.abrupt("return",t.configService.httpClient.get("/pim/categoryimport",{params:{counter:document.getElementById("pim_category_counter").textContent,salesChannelId:t.currentValue},headers:r}).then((function(e){var r=e.data.counter,n=e.data.endcounter,o=(e.data.CategoryPublicationCode,e.data.currentPublicationCode,t.CategoryPublicationCode["PimImport.config.CategoryPublicationCode"]);"error"!=e.data.type?(t.createNotificationSuccess({title:e.data.type,message:e.data.message}),r&&(document.getElementById("pim_category_counter").innerHTML=r,t.vcategory_counter=r-1+"/"+(n-1)+" "+o,r==n||r>n||t.$refs.pimCategoryButton.$el.click())):t.createNotificationError({title:e.data.type,message:e.data.message})})));case 5:case"end":return e.stop()}}),e)})))()},onCategoryOrder:function(){var t=this;return i(regeneratorRuntime.mark((function e(){var r;return regeneratorRuntime.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return r=t.configService.getBasicHeaders(),e.next=3,t.systemConfigApiService.getValues("PimImport");case 3:return t.CategoryPublicationCode=e.sent,e.abrupt("return",t.configService.httpClient.get("/pim/categoryorderimport",{params:{counter:document.getElementById("pim_category_counter").textContent,salesChannelId:t.currentValue},headers:r}).then((function(e){var r=e.data.counter,n=e.data.endcounter,o=(e.data.CategoryPublicationCode,e.data.currentPublicationCode,t.CategoryPublicationCode["PimImport.config.CategoryPublicationCode"]);"error"!=e.data.type?(t.createNotificationSuccess({title:e.data.type,message:e.data.message}),r&&(document.getElementById("pim_category_counter").innerHTML=r,t.vcategory_counter=r-1+"/"+(n-1)+" "+o,r==n||r>n||t.$refs.pimCategoryOrderButton.$el.click())):t.createNotificationError({title:e.data.type,message:e.data.message})})));case 5:case"end":return e.stop()}}),e)})))()},onManageCron:function(){var t=this;return i(regeneratorRuntime.mark((function e(){var r;return regeneratorRuntime.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return r=t.configService.getBasicHeaders(),e.next=3,t.systemConfigApiService.getValues("PimImport");case 3:return t.CategoryPublicationCode=e.sent,e.abrupt("return",t.configService.httpClient.get("/pim/manuallycronmanage",{params:{salesChannelId:t.currentValue},headers:r}).then((function(e){"error"===e.data.type?t.createNotificationError({title:e.data.type,message:e.data.message}):t.createNotificationSuccess({title:e.data.type,message:e.data.message})})));case 5:case"end":return e.stop()}}),e)})))()},onDeleteCategory:function(){var t=this;return i(regeneratorRuntime.mark((function e(){var r;return regeneratorRuntime.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return r=t.configService.getBasicHeaders(),e.next=3,t.systemConfigApiService.getValues("PimImport");case 3:return t.CategoryPublicationCode=e.sent,e.abrupt("return",t.configService.httpClient.get("/pim/categorydelete",{params:{salesChannelId:t.currentValue},headers:r}).then((function(e){e.data.CategoryPublicationCode,e.data.currentPublicationCode,t.CategoryPublicationCode["PimImport.config.CategoryPublicationCode"];"error"!=e.data.type?t.createNotificationSuccess({title:e.data.type,message:e.data.message}):t.createNotificationError({title:e.data.type,message:e.data.message})})));case 5:case"end":return e.stop()}}),e)})))()},onProperty:function(){var t=this;return i(regeneratorRuntime.mark((function e(){var r;return regeneratorRuntime.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return r=t.configService.getBasicHeaders(),e.next=3,t.systemConfigApiService.getValues("PimImport");case 3:return t.PimSettings=e.sent,e.abrupt("return",t.configService.httpClient.get("/pim/propertyImport",{params:{counter:document.getElementById("pim_property_counter").textContent},headers:r}).then((function(e){var r=e.data.counter,n=e.data.endCounter;"error"!==e.data.type?(t.createNotificationSuccess({title:e.data.type,message:e.data.message}),r&&(document.getElementById("pim_property_counter").innerHTML=r,t.pim_property_counter=r-1+"/"+(n-1),r===n||r>n||t.$refs.pimPropertyButton.$el.click())):t.createNotificationError({title:e.data.type,message:e.data.message})})));case 5:case"end":return e.stop()}}),e)})))()},onProductProperty:function(){var t=this;return i(regeneratorRuntime.mark((function e(){var r;return regeneratorRuntime.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return r=t.configService.getBasicHeaders(),e.next=3,t.systemConfigApiService.getValues("PimImport");case 3:return t.PimSettings=e.sent,e.abrupt("return",t.configService.httpClient.get("/pim/ProductPropertyImport",{params:{counter:document.getElementById("pim_product_property_counter").textContent},headers:r}).then((function(e){var r=e.data.counter,n=e.data.endCounter;"error"!==e.data.type?(t.createNotificationSuccess({title:e.data.type,message:e.data.message}),r&&(document.getElementById("pim_product_property_counter").innerHTML=r,t.pim_product_property_counter=r-1+"/"+(n-1),r===n||r>n||t.$refs.pimProductPropertyButton.$el.click())):t.createNotificationError({title:e.data.type,message:e.data.message})})));case 5:case"end":return e.stop()}}),e)})))()},onCategoryCron:function(){var t=this;return i(regeneratorRuntime.mark((function e(){var r;return regeneratorRuntime.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return console.log("Hello Cron"),r=t.configService.getBasicHeaders(),e.next=4,t.systemConfigApiService.getValues("PimImport");case 4:return t.CategoryPublicationCode=e.sent,e.abrupt("return",t.configService.httpClient.get("/pim/categorycronimport",{params:{counter:document.getElementById("pim_category_cron_button").textContent,salesChannelId:t.currentValue},headers:r}).then((function(e){var r=e.data.counter,n=e.data.endcounter,o=(e.data.CategoryPublicationCode,e.data.currentPublicationCode,t.CategoryPublicationCode["PimImport.config.CategoryPublicationCode"]);"error"!=e.data.type?(t.createNotificationSuccess({title:e.data.type,message:e.data.message}),r&&(document.getElementById("pim_category_cron_counter").innerHTML=r,t.vcategory_counter=r-1+"/"+(n-1)+" "+o,r==n||r>n||t.$refs.pimCategoryCronButton.$el.click())):t.createNotificationError({title:e.data.type,message:e.data.message})})));case 6:case"end":return e.stop()}}),e)})))()}}}),Shopware.Module.register("pim-import",{type:"plugin",name:"pim-import.general.mainMenuItemGeneral",title:"pim-import.general.mainMenuItemGeneral",description:"pim-import.general.mainMenuItemGeneral",color:"#ff3d58",icon:"default-action-cloud-download",routes:{list:{component:"pim-import-list",path:"list"}},navigation:[{id:"pim-import-list",label:"pim-import.general.mainMenuItemGeneral",parent:"sw-catalogue",path:"pim.import.list",position:49,color:"#57d9a3"}],settingsItem:{group:"plugins",to:"pim.import.list",icon:"default-text-code",backgroundEnabled:!0}})}});
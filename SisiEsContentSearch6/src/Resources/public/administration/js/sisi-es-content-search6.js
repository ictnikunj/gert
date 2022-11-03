!function(i){var e={};function t(s){if(e[s])return e[s].exports;var n=e[s]={i:s,l:!1,exports:{}};return i[s].call(n.exports,n,n.exports,t),n.l=!0,n.exports}t.m=i,t.c=e,t.d=function(i,e,s){t.o(i,e)||Object.defineProperty(i,e,{enumerable:!0,get:s})},t.r=function(i){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(i,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(i,"__esModule",{value:!0})},t.t=function(i,e){if(1&e&&(i=t(i)),8&e)return i;if(4&e&&"object"==typeof i&&i&&i.__esModule)return i;var s=Object.create(null);if(t.r(s),Object.defineProperty(s,"default",{enumerable:!0,value:i}),2&e&&"string"!=typeof i)for(var n in i)t.d(s,n,function(e){return i[e]}.bind(null,n));return s},t.n=function(i){var e=i&&i.__esModule?function(){return i.default}:function(){return i};return t.d(e,"a",e),e},t.o=function(i,e){return Object.prototype.hasOwnProperty.call(i,e)},t.p="/bundles/sisiescontentsearch6/",t(t.s="hQj6")}({"3qfg":function(i){i.exports=JSON.parse('{"sisi-content":{"list":{"modul":"Content search","shop":"Channel Name","new":"New","label":"label"},"detail":{"list":"Overview","save":"save","language":"Shop/Channel language","label":"label","stemming":"(Stemming) language filter https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-stemmer-tokenfilter.html"}}}')},ZSCd:function(i,e){i.exports='{% block sisi_content_detail %}\n    <sw-page class="sisi-content-detail">\n        <template slot="smart-bar-actions">\n            <sw-button :routerLink="{ name: \'sisi.content.list\' }">\n                {{ $t(\'sisi-content.detail.list\') }}\n            </sw-button>\n\n            <sw-button-process\n                :isLoading="isLoading"\n                :processSuccess="processSuccess"\n                variant="primary"\n                @process-finish="saveFinish"\n                @click="onClickSave">\n                {{ $t(\'sisi-content.detail.save\') }}\n            </sw-button-process>\n        </template>\n\n        <template slot="content">\n                <sw-card v-if="fields" :isLoading="isLoading">\n\n                    <sw-field :label="$t(\'sisi-content.detail.label\')" v-model="fields.label"></sw-field>\n\n                    <sw-select-field :label="$t(\'ssisi-fields.detail.shop\')" size="medium" v-model="fields.shop"\n                                     v-bind:style="{background:colorshop}">\n                        <option> {{ fields.shop }} </option>\n                        <option value=""> no select </option>\n                        <option v-for="option in options" v-bind:value="option.value">\n                            {{ option.text }}\n                        </option>\n                    </sw-select-field>\n\n                    <sw-select-field :label="$t(\'sisi-content.detail.language\')" size="medium" v-model="fields.language">\n                        <option> {{ fields.language }} </option>\n                        <option value=""> no select </option>\n                        <option v-for="option in optionsLanguage" v-bind:value="option.value">\n                            {{ option.text }}\n                        </option>\n                    </sw-select-field>\n                    <sw-select-field :label="$t(\'ssisi-fields.detail.tokenizer\')" v-model="fields.tokenizer"\n                                     :helpText="$t(\'ssisi-fields.detail.help.tokenizer\')" v-on:change="choiceFields()"\n                                     v-bind:style="{background:colorName}">\n                        <option value="classic">Classic tokenizer</option>\n                        <option value="lowercase">Lowercase tokenizer</option>\n                        <option value="edge_ngram"  v-bind:style="{display:strEdge}">Edge_n-gram_tokenizer</option>\n                        <option value="ngram"  v-bind:style="{display:strEdge}">Ngram</option>\n                        <option value="letter">Letter tokenizer</option>\n                        <option value="standard">Standard tokenizer</option>\n                        <option value="whitespace">Whitespace</option>\n                        <option value="keyword">Keyword</option>\n\n                    </sw-select-field>\n                    <sw-number-field :label="$t(\'ssisi-fields.detail.edge\')" v-bind:style="{display:edge}"\n                                     v-model="fields.edge"\n                                     :helpText="$t(\'ssisi-fields.detail.help.edge\')"></sw-number-field>\n\n                    <sw-number-field :label="$t(\'ssisi-fields.detail.minedge\')" v-bind:style="{display:edge}"\n                                     v-model="fields.minedge"\n                                     :helpText="$t(\'ssisi-fields.detail.help.minedge\')"></sw-number-field>\n\n                    <sw-select-field :label="$t(\'ssisi-fields.detail.filter1\')" size="medium" v-model="fields.filter1"\n                                     v-on:change="choiceFields()" :helpText="$t(\'ssisi-fields.detail.help.filter\')">\n                        <option value="noselect">  {{ $t(\'ssisi-fields.detail.noselect\') }}</option>\n                        <option value="lowercase"> {{ $t(\'ssisi-fields.detail.lowercase\') }}</option>\n                        <option value="classic"> Classic</option>\n                        <option value="truncate"> Truncate</option>\n                        <option value="autocomplete"> Autocomplete</option>\n                        <option value="synonym"> synonym</option>\n                        <option value="word_delimiter_graph"> Word_delimiter_graph</option>\n                    </sw-select-field>\n\n                    <sw-select-field :label="$t(\'ssisi-fields.detail.filter2\')" v-model="fields.filter2"\n                                     v-on:change="choiceFields()" :helpText="$t(\'ssisi-fields.detail.help.filter\')">\n                        <option value="noselect">  {{ $t(\'ssisi-fields.detail.noselect\') }}</option>\n                        <option value="lowercase"> {{ $t(\'ssisi-fields.detail.lowercase\') }}</option>\n                        <option value="classic"> Classic</option>\n                        <option value="truncate"> Truncate</option>\n                        <option value="autocomplete"> Autocomplete</option>\n                        <option value="synonym"> synonym</option>\n                        <option value="word_delimiter_graph"> Word_delimiter_graph</option>\n                    </sw-select-field>\n\n                    <sw-select-field :label="$t(\'ssisi-fields.detail.filter3\')" v-model="fields.filter3"\n                                     v-on:change="choiceFields()" :helpText="$t(\'ssisi-fields.detail.help.filter\')">\n                        <option value="noselect">  {{ $t(\'ssisi-fields.detail.noselect\') }}</option>\n                        <option value="lowercase"> {{ $t(\'ssisi-fields.detail.lowercase\') }}</option>\n                        <option value="classic"> Classic</option>\n                        <option value="truncate"> Truncate</option>\n                        <option value="autocomplete"> Autocomplete</option>\n                        <option value="synonym"> synonym</option>\n                        <option value="word_delimiter_graph"> Word_delimiter_graph</option>\n                    </sw-select-field>\n                    <sw-select-field :label="$t(\'sisi-content.detail.stemming\')" v-model="fields.stemming"\n                                     v-on:change="changeStemming()"\n                                     :helpText="$t(\'ssisi-fields.detail.help.stemming\')">\n                        <option value="noselect">  {{ $t(\'ssisi-fields.detail.noselect\') }}</option>\n                        <option value="arabic"> {{ $t(\'ssisi-fields.detail.stemmings.arabic\') }}</option>\n                        <option value="armenian"> {{ $t(\'ssisi-fields.detail.stemmings.armenian\') }}</option>\n                        <option value="basque"> {{ $t(\'ssisi-fields.detail.stemmings.basque\') }}</option>\n                        <option value="bengali"> {{ $t(\'ssisi-fields.detail.stemmings.bengali\') }}</option>\n                        <option value="brazilian"> {{ $t(\'ssisi-fields.detail.stemmings.brazilian\') }}</option>\n                        <option value="bulgarian"> {{ $t(\'ssisi-fields.detail.stemmings.bulgarian\') }}</option>\n                        <option value="catalan"> {{ $t(\'ssisi-fields.detail.stemmings.catalan\') }}</option>\n                        <option value="czech"> {{ $t(\'ssisi-fields.detail.stemmings.czech\') }}</option>\n                        <option value="danish"> {{ $t(\'ssisi-fields.detail.stemmings.danish\') }}</option>\n                        <option value="dutch"> {{ $t(\'ssisi-fields.detail.stemmings.dutch\') }}</option>\n                        <option value="dutch_kp"> {{ $t(\'ssisi-fields.detail.stemmings.dutch_kp\') }}</option>\n                        <option value="english"> {{ $t(\'ssisi-fields.detail.stemmings.english\') }}</option>\n                        <option value="light_english"> {{ $t(\'ssisi-fields.detail.stemmings.light_english\') }}</option>\n                        <option value="lovins"> {{ $t(\'ssisi-fields.detail.stemmings.lovins\') }}</option>\n                        <option\n                            value="minimal_english"> {{ $t(\'ssisi-fields.detail.stemmings.minimal_english\') }}</option>\n                        <option value="porter2"> {{ $t(\'ssisi-fields.detail.stemmings.porter2\') }}</option>\n                        <option\n                            value="possessive_english"> {{ $t(\'ssisi-fields.detail.stemmings.possessive_english\') }}</option>\n                        <option value="estonian"> {{ $t(\'ssisi-fields.detail.stemmings.estonian\') }}</option>\n                        <option value="finnish"> {{ $t(\'ssisi-fields.detail.stemmings.finnish\') }}</option>\n                        <option value="light_finnish"> {{ $t(\'ssisi-fields.detail.stemmings.light_finnish\') }}</option>\n                        <option value="light_french"> {{ $t(\'ssisi-fields.detail.stemmings.light_french\') }}</option>\n                        <option value="french"> {{ $t(\'ssisi-fields.detail.stemmings.french\') }}</option>\n                        <option\n                            value="minimal_french"> {{ $t(\'ssisi-fields.detail.stemmings.minimal_french\') }}</option>\n                        <option value="galician"> {{ $t(\'ssisi-fields.detail.stemmings.galician\') }}</option>\n                        <option\n                            value="minimal_galician"> {{ $t(\'ssisi-fields.detail.stemmings.minimal_galician\') }}</option>\n                        <option value="light_german"> {{ $t(\'ssisi-fields.detail.stemmings.light_german\') }}</option>\n                        <option value="german"> {{ $t(\'ssisi-fields.detail.stemmings.german\') }}</option>\n                        <option value="german2"> {{ $t(\'ssisi-fields.detail.stemmings.german2\') }}</option>\n                        <option\n                            value="minimal_german"> {{ $t(\'ssisi-fields.detail.stemmings.minimal_german\') }}</option>\n                        <option value="greek"> {{ $t(\'ssisi-fields.detail.stemmings.greek\') }}</option>\n                        <option value="hindi"> {{ $t(\'ssisi-fields.detail.stemmings.hindi\') }}</option>\n                        <option value="hungarian"> {{ $t(\'ssisi-fields.detail.stemmings.hungarian\') }}</option>\n                        <option\n                            value="light_hungarian"> {{ $t(\'ssisi-fields.detail.stemmings.light_hungarian\') }}</option>\n                        <option value="indonesian"> {{ $t(\'ssisi-fields.detail.stemmings.indonesian\') }}</option>\n                        <option value="irish"> {{ $t(\'ssisi-fields.detail.stemmings.irish\') }}</option>\n                        <option value="light_italian"> {{ $t(\'ssisi-fields.detail.stemmings.light_italian\') }}</option>\n                        <option value="italian"> {{ $t(\'ssisi-fields.detail.stemmings.italian\') }}</option>\n                        <option value="sorani"> {{ $t(\'ssisi-fields.detail.stemmings.sorani\') }}</option>\n                        <option value="latvian"> {{ $t(\'ssisi-fields.detail.stemmings.latvian\') }}</option>\n                        <option value="lithuanian"> {{ $t(\'ssisi-fields.detail.stemmings.lithuanian\') }}</option>\n                        <option value="norwegian"> {{ $t(\'ssisi-fields.detail.stemmings.norwegian\') }}</option>\n                        <option\n                            value="light_norwegian"> {{ $t(\'ssisi-fields.detail.stemmings.light_norwegian\') }}</option>\n                        <option\n                            value="minimal_norwegian">{{ $t(\'ssisi-fields.detail.stemmings. minimal_norwegian\') }}</option>\n                        <option value="light_nynorsk"> {{ $t(\'ssisi-fields.detail.stemmings.light_nynorsk\') }}</option>\n                        <option value="nynorsk"> {{ $t(\'ssisi-fields.detail.stemmings.minimal_nynorsk\') }}</option>\n                        <option\n                            value="light_portuguese"> {{ $t(\'ssisi-fields.detail.stemmings.light_portuguese\') }}</option>\n                        <option\n                            value="minimal_portuguese"> {{ $t(\'ssisi-fields.detail.stemmings.minimal_portuguese\') }}</option>\n                        <option value="portuguese"> {{ $t(\'ssisi-fields.detail.stemmings.portuguese\') }}</option>\n                        <option\n                            value="portuguese_rslp"> {{ $t(\'ssisi-fields.detail.stemmings.portuguese_rslp\') }}</option>\n                        <option value="romanian"> {{ $t(\'ssisi-fields.detail.stemmings.romanian\') }}</option>\n                        <option value="russian"> {{ $t(\'ssisi-fields.detail.stemmings.russian\') }}</option>\n                        <option value="light_russian"> {{ $t(\'ssisi-fields.detail.stemmings.light_russian\') }}</option>\n                        <option value="light_spanish"> {{ $t(\'ssisi-fields.detail.stemmings.light_spanish\') }}</option>\n                        <option value="spanish"> {{ $t(\'ssisi-fields.detail.stemmings.spanish\') }}</option>\n                        <option value="swedish"> {{ $t(\'ssisi-fields.detail.stemmings.swedish\') }}</option>\n                        <option value="light_swedish"> {{ $t(\'ssisi-fields.detail.stemmings.light_swedish\') }}</option>\n                        <option value="turkish"> {{ $t(\'ssisi-fields.detail.stemmings.turkish\') }}</option>\n                    </sw-select-field>\n                    <sw-select-field :label="$t(\'ssisi-fields.detail.stemming_stop\')" v-model="fields.stemmingstop"\n                                     v-bind:style="{display:stemmingStr}"\n                                     :helpText="$t(\'ssisi-fields.detail.help.stemming_stop\')">\n                        <option value="yes"> {{ $t(\'ssisi-fields.detail.booleanYes\') }}</option>\n                        <option value="No">{{ $t(\'ssisi-fields.detail.booleanNo\') }}</option>\n                    </sw-select-field>\n                    <sw-textarea-field :label="$t(\'ssisi-fields.detail.stop\')" v-model="fields.stop"\n                                       :helpText="$t(\'ssisi-fields.detail.help.stop\')"></sw-textarea-field>\n\n                    <sw-select-field :label="$t(\'ssisi-fields.detail.strip_str\')" v-model="fields.format"\n                                     :helpText="$t(\'ssisi-fields.detail.help.strip_str\')">\n                        <option value="yes"> {{ $t(\'ssisi-fields.detail.booleanYes\') }} </option>\n                        <option value="no"> {{ $t(\'ssisi-fields.detail.booleanNo\') }} </option>\n                    </sw-select-field>\n\n                    <sw-textarea-field :label="$t(\'ssisi-fields.detail.strip\')" v-model="fields.pattern"\n                                       :helpText="$t(\'ssisi-fields.detail.help.strip\')">\n                </sw-card>\n            </sw-card-view>\n\n\n        </template>\n    </sw-page>\n{% endblock %}\n'},hQj6:function(i,e,t){"use strict";t.r(e);var s=t("lyU1"),n=t.n(s),o=Shopware.Component,l=Shopware.Data.Criteria;o.register("sisi-content-list",{template:n.a,inject:["repositoryFactory"],data:function(){return{fields:null,isLoading:!0,sortBy:"createdAt",sortDirection:"DESC"}},metaInfo:function(){return{title:this.$createTitle()}},methods:{},computed:{teaserColumns:function(){return[{property:"label",dataIndex:"label",label:this.$t("sisi-content.list.label"),inlineEdit:"string",allowResize:!0,primary:!0},{property:"shop",dataIndex:"shop",label:this.$t("sisi-content.list.shop"),inlineEdit:"string",allowResize:!0,primary:!0}]}},created:function(){var i=this;this.repository=this.repositoryFactory.create("sisi_escontent_fields"),this.repository.search(new l,Shopware.Context.api).then((function(e){i.fields=e}))}});t("tfbq");var a=t("ZSCd"),d=t.n(a),p=Shopware,r=p.Component;p.Mixin,Shopware.Application.getContainer("init").httpClient;r.register("sisi-content-detail",{template:d.a,inject:["repositoryFactory","SisiApiCredentialsService"],metaInfo:function(){return{title:this.$createTitle()}},data:function(){return{fields:null,isLoading:!1,processSuccess:!1,shop:"",options:[],optininsstring:"",optionsLanguage:[],language:"",tokenizer:"",edge:"none",stemmingStr:"none",colorName:"white",colorshop:"white",strEdge:"block"}},computed:{},created:function(){this.repository=this.repositoryFactory.create("sisi_escontent_fields"),this.getBundle(),this.getChannels(),this.choiceFields()},methods:{getBundle:function(){var i=this;this.repository.get(this.$route.params.id,Shopware.Context.api).then((function(e){i.fields=e,i.choiceFields()}))},onClickSave:function(){var i=this;this.validate(),this.strvalidate&&(this.isLoading=!0,this.repository.save(this.fields,Shopware.Context.api).then((function(){i.getBundle(),i.isLoading=!1,i.processSuccess=!0})).catch((function(e){i.isLoading=!1})))},saveFinish:function(){this.processSuccess=!1},choiceFields:function(){"Edge_n-gram_tokenizer"===this.fields.tokenizer||"ngram"===this.fields.tokenizer||"edge_ngram"===this.fields.tokenizer||"autocomplete"===this.fields.filter1||"autocomplete"===this.fields.filter2||"autocomplete"===this.fields.filter3?this.edge="block":this.edge="none","autocomplete"===this.fields.filter1||"autocomplete"===this.fields.filter2||"autocomplete"===this.fields.filter3?this.strEdge="none":this.strEdge="block","simple_pattern"===this.fields.tokenizer?this.pattern="block":this.pattern="none"},changeStemming:function(){"noselect"!==this.fields.stemming?this.stemmingStr="block":this.stemmingStr="none"},validate:function(){this.strvalidate=!0,""===this.fields.tokenizer||null==this.fields.tokenizer?(this.strvalidate=!1,this.colorName="red"):this.colorName="white",""===this.fields.shop||null==this.fields.shop?(this.strvalidate=!1,this.colorshop="red"):this.colorshop="white"},getChannels:function(){var i=this;this.SisiApiCredentialsService.channels().then((function(e){i.options=e.channel,i.optionsLanguage=e.language,i.optionsLanguage.shift()})).catch((function(i){}))}}});var c=t("xHXB"),m=t("3qfg");Shopware.Module.register("sisi-content",{type:"plugin",name:"sisi-content",title:"sisi-content.list.modul",description:"search sisi-content",color:"#ff3d58",icon:"default-shopping-paper-bag-product",entity:"sisi_escontent_fields",snippets:{"de-DE":c,"en-GB":m},routes:{list:{component:"sisi-content-list",path:"list"},detail:{component:"sisi-content-detail",path:"detail/:id"},create:{component:"sisi-content-create",path:"create"}},settingsItem:{group:"plugins",to:"sisi.content.list",icon:"default-action-search",backgroundEnabled:!0},navigation:[{id:"sisi-content-search",label:"sisi-content.list.modul",color:"#57D9A3",path:"sisi.content.list",icon:"default-symbol-products",parent:"sw-catalogue",privilege:"product.viewer",position:12}]})},lyU1:function(i,e){i.exports='{% block sisi_fields_list %}\n    <sw-page class="sisi-fields-list">\n\n        {% block sisi_fields_list_smart_bar_actions %}\n\n            <template slot="smart-bar-actions">\n\n                <sw-button variant="primary" :routerLink="{ name: \'sisi.content.create\' }">\n                    {{ $t(\'sisi-content.list.new\') }}\n                </sw-button>\n            </template>\n        {% endblock %}\n\n\n        <template slot="content">\n            {% block sisi_fields_list_content %}\n                <sw-entity-listing\n                    v-if="fields"\n                    :items="fields"\n                    :repository="repository"\n                    :showSelection="false"\n                    :columns="teaserColumns"\n                    detailRoute="sisi.content.detail"\n                >\n                </sw-entity-listing>\n            {% endblock %}\n        </template>\n\n\n    </sw-page>\n{% endblock %}\n'},tfbq:function(i,e){Shopware.Component.extend("sisi-content-create","sisi-content-detail",{methods:{getBundle:function(){this.fields=this.repository.create(Shopware.Context.api)},onClickSave:function(){var i=this;this.isLoading=!0,this.validate(),this.strvalidate?this.repository.save(this.fields,Shopware.Context.api).then((function(){i.isLoading=!1,i.$router.push({name:"sisi.content.detail",params:{id:i.fields.id}})})).catch((function(e){console.log(e),i.isLoading=!1})):this.isLoading=!1}}})},xHXB:function(i){i.exports=JSON.parse('{"sisi-content":{"list":{"modul":"Content Suche","shop":"Channel Name","new":"Neu","label":"Beschriftung"},"detail":{"list":"Zurück zur Übersichtseite","save":"Speichern","language":"Shop/Channel  Sprache","label":"Beschriftung","stemming":"(Stemming) sprach Filter https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-stemmer-tokenfilter.html"}}}')}});
//# sourceMappingURL=sisi-es-content-search6.js.map
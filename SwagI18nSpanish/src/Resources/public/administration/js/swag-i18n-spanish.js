(this.webpackJsonp=this.webpackJsonp||[]).push([["swag-i18n-spanish"],{"+U+I":function(n,e,a){"use strict";a.r(e);a("3p7s");var t=a("E+yk"),s=a.n(t);a("TRCT");const{Component:i}=Shopware;i.override("sw-plugin-list",{template:s.a,computed:{pluginColumns(){return this.$super("pluginColumns").reduce((n,e)=>("label"===e.property&&(e.multiLine=!0),n.push(e),n),[])}}})},"3p7s":function(n,e){!1===Shopware.Locale.getByName("es-ES")&&Shopware.Locale.register("es-ES",{})},"E+yk":function(n,e){n.exports='{% block sw_plugin_list_grid_columns_label_label %}\n    {% parent %}\n\n    <sw-alert v-if="item.composerName === \'swag/i18n-spanish\'"\n              class="swag-i18n__plugin-deprecation-alert"\n              variant="warning">\n        {{ $tc(\'swag-i18n.deprecationAlert\') }}\n\n        <a class="swag-i18n__plugin-deprecation-alert-link"\n           :href="$tc(\'swag-i18n.readMoreLink\')"\n           target="_blank">\n            {{ $tc(\'swag-i18n.readMore\') }}\n        </a>\n\n        <template #customIcon>\n            <sw-icon class="sw-alert__icon swag-i18n__plugin-deprecation-alert-icon"\n                     name="default-badge-warning"\n                     size="12px"\n                     decorative>\n            </sw-icon>\n        </template>\n    </sw-alert>\n{% endblock %}\n'},TRCT:function(n,e,a){var t=a("lltA");"string"==typeof t&&(t=[[n.i,t,""]]),t.locals&&(n.exports=t.locals);(0,a("SZ7m").default)("2b54458c",t,!0,{})},lltA:function(n,e,a){}},[["+U+I","runtime","vendors-node"]]]);
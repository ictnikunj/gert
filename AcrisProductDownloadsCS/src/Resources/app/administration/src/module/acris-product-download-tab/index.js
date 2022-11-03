import './page/acris-product-download-tab-list';
import './page/acris-product-download-tab-create';
import './page/acris-product-download-tab-detail';
import './acris-settings-item.scss';

import deDE from "./snippet/de-DE";
import enGB from "./snippet/en-GB";

const { Module } = Shopware;

Module.register('acris-product-download-tab', {
    type: 'plugin',
    name: 'AcrisProductDownloads',
    title: 'acris-product-download-tab.general.mainMenuItemGeneral',
    description: 'acris-product-download-tab.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#a6c836',
    icon: 'default-action-duplicate',
    favicon: 'icon-module-settings.png',
    entity: 'acris_download_tab',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            component: 'acris-product-download-tab-list',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index'
            }
        },
        detail: {
            component: 'acris-product-download-tab-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'acris-product.download.tab.index'
            }
        },
        create: {
            component: 'acris-product-download-tab-create',
            path: 'create',
            meta: {
                parentPath: 'acris-product.download.tab.index'
            }
        }
    },

    settingsItem: [
        {
            name:   'acris-product-download-tab',
            to:     'acris.product.download.tab.index',
            label:  'acris-product-download-tab.general.mainMenuItemGeneral',
            group:  'plugins',
            icon:   'default-action-duplicate'
        }
    ]
});

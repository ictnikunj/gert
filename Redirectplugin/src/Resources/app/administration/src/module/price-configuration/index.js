import './page/price-config';
const { Module } = Shopware;

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Module.register('price-configuration', {
    type: 'plugin',
    name: 'priceconfiguration.name',
    title: 'priceconfiguration.name',
    description: 'priceconfiguration.name',
    color: '#ff3d58',
    icon: 'default-action-settings',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },
    routes: {
        settings: {
            component: 'price-config',
            path: 'settings',
            meta: {
                parentPath: 'sw.settings.index',
            }
        },
    },

    settingsItem: {
        group: 'plugins',
        to: 'price.configuration.settings',
        icon: 'default-action-cloud-upload',
        backgroundEnabled: true,
    }

});

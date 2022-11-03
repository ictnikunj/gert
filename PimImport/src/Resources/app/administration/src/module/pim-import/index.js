import './page/pim-import-list';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';
const { Module } = Shopware;

Module.register('pim-import', {
    type: 'plugin',
    name: 'pim-import.general.mainMenuItemGeneral',
    title: 'pim-import.general.mainMenuItemGeneral',
    description: 'pim-import.general.mainMenuItemGeneral',
    color: '#ff3d58',
    icon: 'default-action-cloud-download',

    routes: {
        list: {
            component: 'pim-import-list',
            path: 'list'
        },
    },


    navigation: [{
        id: 'pim-import-list',
        label: 'pim-import.general.mainMenuItemGeneral',
        parent: 'sw-catalogue',
        path: 'pim.import.list',
        position: 49,
        color: '#57d9a3',
    }],

    settingsItem: {
        group: 'plugins',
        to: 'pim.import.list',
        icon: 'default-text-code',
        backgroundEnabled: true,
    }

});

import './content/sisi-content-list';
import './content/sisi-content-create';
import './content/sisi-content-detail';


import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const {Module} = Shopware;

Module.register('sisi-content', {
    type: 'plugin',
    name: 'sisi-content',
    title: 'sisi-content.list.modul',
    description: 'search sisi-content',
    color: '#ff3d58',
    icon: 'default-shopping-paper-bag-product',
    entity: 'sisi_escontent_fields',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'sisi-content-list',
            path: 'list'
        },
        detail: {
            component: 'sisi-content-detail',
            path: 'detail/:id'

        },
        create: {
            component: 'sisi-content-create',
            path: 'create',
        }

    },

    settingsItem: {
        group: 'plugins',
        to: 'sisi.content.list',
        icon: 'default-action-search',
        backgroundEnabled: true
    },

    navigation: [{
        id: 'sisi-content-search',
        label: 'sisi-content.list.modul',
        color: '#57D9A3',
        path: 'sisi.content.list',
        icon: 'default-symbol-products',
        parent: 'sw-catalogue',
        privilege: 'product.viewer',
        position: 12
    }]
});

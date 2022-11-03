import './page/media-redirect-list';
import './page/media-redirect-detail';
import './page/media-redirect-create';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';
const { Module } = Shopware;

Module.register('media-redirect', {
    type: 'plugin',
    name: 'media-redirect.general.mainMenuItemGeneral',
    title: 'media-redirect.general.mainMenuItemGeneral',
    description: 'media-redirect.general.descriptionTextModule',
    color: '#ff3d58',
    icon: 'default-action-bulk-edit',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'media-redirect-list',
            path: 'list'
        },
        detail: {
            component: 'media-redirect-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'media.redirect.list'
            }
        },
        create: {
            component: 'media-redirect-create',
            path: 'create',
            meta: {
                parentPath: 'media.redirect.list'
            }
        }
    },

    navigation: [{
        label: 'media-redirect.general.mainMenuItemGeneral',
        color: '#ff3d58',
        path: 'media.redirect.list',
        icon: 'default-action-bulk-edit',
        parent: 'sw-content'
    }]
});

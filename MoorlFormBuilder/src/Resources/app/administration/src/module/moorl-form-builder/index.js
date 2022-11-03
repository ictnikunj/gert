const { Module } = Shopware;

import './component';
import './page/list';
import './page/detail';
import './page/create';

Module.register('moorl-form-builder', {
    type: 'plugin',
    name: 'moorl-form-builder',
    title: 'moorl-form-builder.general.mainMenuItemGeneral',
    color: '#ffcab1',
    icon: 'default-object-books-books-a',
    routes: {
        list: {
            component: 'moorl-form-builder-list',
            path: 'list',
            meta: {
                privilege: 'moorl_form:read'
            }
        },
        detail: {
            component: 'moorl-form-builder-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'moorl.form.builder.list',
                privilege: 'moorl_form:read'
            }
        },
        create: {
            component: 'moorl-form-builder-create',
            path: 'create',
            meta: {
                parentPath: 'moorl.form.builder.list',
                privilege: 'moorl_form:read'
            }
        }
    },
    navigation: [{
        label: 'moorl-form-builder.general.mainMenuItemGeneral',
        color: '#ffcab1',
        path: 'moorl.form.builder.list',
        position: 300,
        parent: 'sw-content'
    }]
});

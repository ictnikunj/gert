const { Module } = Shopware;
import './page/list';

Module.register('moorl-form-builder-history', {
    type: 'plugin',
    name: 'moorl-form-builder-history',
    title: 'moorl-form-builder.general.mainMenuItemHistory',
    color: '#ffcab1',
    icon: 'default-object-books-books-a',
    routes: {
        list: {
            component: 'moorl-form-builder-history-list',
            path: 'list',
            meta: {
                privilege: 'moorl_form_history:read',
                parentPath: 'sw.settings.index'
            }
        }
    },
    settingsItem: [
        {
            privilege: 'moorl_form_history:read',
            name: 'moorl-form-builder-history-list',
            to: 'moorl.form.builder.history.list',
            group: 'plugins',
            icon: 'default-object-globe',
            label: 'moorl-form-builder.general.mainMenuItemHistory'
        }
    ]
});

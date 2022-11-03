import './page/bst-cron-manager-list';
import './page/bst-cron-manager-detail';

const { Module } = Shopware;

Module.register('bst-cron-manager', {
    type: 'plugin',
    name: 'BstCronManager6',
    title: 'bst-cron-manager.title',
    description: 'bst-cron-manager.description',
    color: '#9AA8B5',
    icon: 'default-time-clock',
    favicon: 'icon-module-settings.png',
    entity: 'scheduled_task',

    routes: {
        index: {
            components: {
                default: 'bst-cron-manager-list'
            },
            path: 'index'
        },
        detail: {
            component: 'bst-cron-manager-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'bst.cron.manager.index'
            },
            props: {
                default(route) {
                    return {
                        cronjobId: route.params.id
                    };
                }
            }
        }
    },

    settingsItem: {
        privilege: 'system.system_config',
        to: 'bst.cron.manager.index',
        group: 'system',
        icon: 'default-time-clock'
    }
});

import './module/bst-cron-manager';
import template from './sw-search-bar-item.html.twig';

const { Application } = Shopware;

Application.addServiceProviderDecorator('searchTypeService', searchTypeService => {
    searchTypeService.upsertType('scheduled_task', {
        entityName: 'scheduled_task',
        entityService: 'scheduledTaskService',
        placeholderSnippet: 'bst-cron-manager.list.textCronTasksOverview',
        listingRoute: 'bst.cron.manager.index'
    });

    return searchTypeService;
});

Shopware.Component.override('sw-search-bar-item', {
    template
})

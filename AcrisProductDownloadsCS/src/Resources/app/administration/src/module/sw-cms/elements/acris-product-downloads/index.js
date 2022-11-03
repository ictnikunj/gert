import './component';
import './config';
import './preview';

const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria();

Shopware.Service('cmsService').registerCmsElement({
    name: 'acris-product-downloads',
    label: 'acris-product-downloads.elements.productDownloads.label',
    component: 'sw-cms-el-acris-product-downloads',
    configComponent: 'sw-cms-el-config-acris-product-downloads',
    previewComponent: 'sw-cms-el-preview-acris-product-downloads',
    defaultConfig: {
        product: {
            source: 'static',
            value: null,
            required: true,
            entity: {
                name: 'product',
                criteria: criteria
            }
        },
        showHeadline: {
            source: 'static',
            value: true
        },
        backgroundColor: {
            source: 'static',
            value: ''
        },
        paddingTop: {
            source: 'static',
            value: 'no'
        },
        paddingBot: {
            source: 'static',
            value: 'pb-3'
        },
        paddingLeft: {
            source: 'static',
            value: 'no'
        },
        paddingRight: {
            source: 'static',
            value: 'no'
        },
        paddingTopIndividual: {
            source: 'static',
            value: '10'
        },
        paddingBotIndividual: {
            source: 'static',
            value: '10'
        },
        paddingLeftIndividual: {
            source: 'static',
            value: '10'
        },
        paddingRightIndividual: {
            source: 'static',
            value: '10'
        },
        previewImageAlign: {
            source: 'static',
            value: 'left'
        },
        thumbnailLayout: {
            source: 'static',
            value: 'square'
        },
        borderActive: {
            source: 'static',
            value: false
        },
        borderColor: {
            source: 'static',
            value: ''
        },
        borderSize: {
            source: 'static',
            value: '1'
        },
        borderRadius: {
            source: 'static',
            value: '0'
        }
    },
    collect: function collect(elem) {
        const context = {
            ...Shopware.Context.api,
            inheritance: true
        };

        const criteriaList = {};

        Object.keys(elem.config).forEach((configKey) => {
            if (elem.config[configKey].source === 'mapped') {
                return;
            }

            const config = elem.config[configKey];
            const configEntity = config.entity;
            const configValue = config.value;

            if (!configEntity || !configValue) {
                return;
            }

            const entityKey = configEntity.name;
            const entityData = {
                value: [configValue],
                key: configKey,
                searchCriteria: configEntity.criteria ? configEntity.criteria : new Criteria(),
                ...configEntity
            };

            entityData.searchCriteria.setIds(entityData.value);
            entityData.context = context;

            entityData.searchCriteria.addAssociation('acrisDownloads');
            entityData.searchCriteria.getAssociation('acrisDownloads')
                .addSorting(Criteria.sort('position'))
                .addSorting(Criteria.sort('title', 'ASC', true))
                .addAssociation('media');

            criteriaList[`entity-${entityKey}`] = entityData;
        });

        return criteriaList;
    }
});

import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'acris-product-downloads',
    label: 'acris-product-downloads.blocks.commerce.productDownloads.label',
    category: 'commerce',
    component: 'sw-cms-block-acris-product-downloads',
    previewComponent: 'sw-cms-preview-acris-product-downloads',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        column: {
            type: 'acris-product-downloads'
        }
    }
});

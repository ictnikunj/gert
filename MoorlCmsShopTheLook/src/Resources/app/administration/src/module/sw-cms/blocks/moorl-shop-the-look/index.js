import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'moorl-shop-the-look',
    label: 'moorl-cms.blocks.general.shopTheLook.label',
    category: 'moorl-cms-old',
    component: 'sw-cms-block-moorl-shop-the-look',
    previewComponent: 'sw-cms-preview-moorl-shop-the-look',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        one: {
            type: 'moorl-shop-the-look'
        }
    }
});

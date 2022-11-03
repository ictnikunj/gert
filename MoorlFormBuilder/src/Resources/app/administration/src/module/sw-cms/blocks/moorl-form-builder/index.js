import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'moorl-form-builder',
    label: 'moorl-cms.blocks.general.moorlFormBuilder.label',
    category: 'form',
    component: 'sw-cms-block-moorl-form-builder',
    previewComponent: 'sw-cms-preview-moorl-form-builder',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        one: {
            type: 'moorl-form-builder'
        }
    }
});

const {Application} = Shopware;
const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria();

import './component';
import './config';
import './preview';

Application.getContainer('service').cmsService.registerCmsElement({
    plugin: 'MoorlFormBuilder',
    icon: 'default-communication-envelope',
    name: 'moorl-form-builder',
    label: 'moorl-cms.blocks.general.moorlFormBuilder.label',
    component: 'sw-cms-el-moorl-form-builder',
    configComponent: 'sw-cms-el-config-moorl-form-builder',
    previewComponent: 'sw-cms-el-preview-moorl-form-builder',
    defaultConfig: {
        form: {
            source: 'static',
            value: null,
            entity: {
                name: 'moorl_form',
                criteria: criteria
            }
        },
        emailReceiver: {
            source: 'static',
            value: null,
        }
    }
});

import './sh-autocomplete';
import MoorlFormBuilderV2Plugin from './moorl-form-builder-v2/moorl-form-builder-v2.plugin';
//import MoorlFormBuilder from './moorl-form-builder/moorl-form-builder';
import MoorlCaptcha from './moorl-captcha/moorl-captcha';

const PluginManager = window.PluginManager;
PluginManager.register('MoorlFormBuilder', MoorlFormBuilderV2Plugin, '[data-moorl-form-builder]');
//PluginManager.register('MoorlFormBuilder', MoorlFormBuilder, '[data-moorl-form-builder]');
PluginManager.register('MoorlCaptcha', MoorlCaptcha);

if (module.hot) {
    module.hot.accept();
}

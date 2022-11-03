import './brandcrock-mega-menu/brandcrock-mega-menu.js';
import BrandcrockMegaMenu from './brandcrock-mega-menu/brandcrock-mega-menu.plugin.js';
const PluginManager = window.PluginManager;
// PluginManager.override('FlyoutMenu', BrandcrockMegaMenu, '[data-flyout-menu]');
// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}

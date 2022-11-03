import ClosePopup from './sisi-search/ClosePopup';
import Paging from './sisi-search/Paging';
import Filter from './sisi-search/Filter';

// Register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('ClosePopup', ClosePopup, '.header-main');
PluginManager.register('Paging', Paging, 'body');
PluginManager.register('Filter', Filter, '.sisiFilter');

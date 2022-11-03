
const {Module} = Shopware;

Module.register('custom-module', {
    type: 'plugin',
    name: 'Custom',
    title: 'Custom module',
    description: 'Description for your custom module',
    color: '#62ff80',
    icon: 'default-object-lab-flask',
    routes: {
        overview: {
            component: 'script-button',
            path: 'overview'
        }
    }, 
 
    navigation: [{  
        label: 'Custom Module',
        color: '#62ff80',
        path: 'custom.module.overview',
        icon: 'default-object-lab-flask'
    }]
});  
  
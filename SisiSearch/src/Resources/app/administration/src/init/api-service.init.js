import SisiApiCredentialsService from '../api/sisiApiCredentialsService';

const {Application} = Shopware;

Application.addServiceProvider('SisiApiCredentialsService', (container) => {
    const initContainer = Application.getContainer('init');
    return new SisiApiCredentialsService(initContainer.httpClient, container.loginService);
});

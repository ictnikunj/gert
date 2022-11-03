const {Module} = Shopware;

import './page/sw-product-detail';
import './view/sw-product-detail-fb';

Module.register('sw-product-detail-fb-tab', {
    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.product.detail') {
            currentRoute.children.push({
                name: 'sw.product.detail.fb',
                path: '/sw/product/detail/:id/fb',
                component: 'sw-product-detail-fb',
                meta: {
                    parentPath: "sw.product.index"
                }
            });
        }
        next(currentRoute);
    }
});

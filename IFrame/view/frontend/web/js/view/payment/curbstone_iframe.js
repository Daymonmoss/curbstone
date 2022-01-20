define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'curbstone_iframe',
                component: 'Curbstone_IFrame/js/view/payment/method-renderer/curbstone_iframe-method'
            }
        );
        return Component.extend({});
    }
);

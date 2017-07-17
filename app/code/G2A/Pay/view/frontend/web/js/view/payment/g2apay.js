/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function(
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type     : 'g2apay',
                component: 'G2A_Pay/js/view/payment/method-renderer/g2apay-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);

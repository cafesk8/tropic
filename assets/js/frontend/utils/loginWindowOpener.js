import Register from 'framework/common/utils/Register';
import Window from './Window';
import PaymentTransportRelations from '../order/order';
import Ajax from 'framework/common/utils/Ajax';

new Register().registerCallback(function ($container) {
    $container.filterAllNodes('.js-login-in-order-window-opener').on('click', function (event) {
        const $target = $(event.currentTarget);
        Ajax.ajax({
            type: 'POST',
            data: { email: $target.data('email') },
            url: $target.data('url'),
            success: function (data) {
                /* eslint-disable no-new */
                new Window({
                    content: data,
                    cssClass: 'window-popup--standard window-popup--no-padding',
                    eventOnLoad: PaymentTransportRelations.copyEmail
                });
            }
        });

        return false;
    });
});

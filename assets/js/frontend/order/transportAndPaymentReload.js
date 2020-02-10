import Ajax from 'framework/common/utils/ajax';
import Register from 'framework/common/utils/register';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.transportAndPaymentReload = Shopsys.transportAndPaymentReload || {};

    Shopsys.transportAndPaymentReload.TransportAndPaymentReload = function ($transportCountryInput) {

        this.init = function () {
            $transportCountryInput.change(this.onTransportCountryInputChange);
        };

        this.onTransportCountryInputChange = function () {
            Ajax.ajax({
                type: 'get',
                url: $transportCountryInput.attr('data-order-transport-and-payment-box-url'),
                dataType: 'html',
                data: {
                    'countryId': $transportCountryInput.val()
                },
                success: function (data) {
                    $('.js-order-box').html(data);
                    new Register().registerNewContent($('.js-order-box'));
                }
            });
        };
    };

    new Register().registerCallback(function ($container) {
        $container.filterAllNodes('.js-transport-country').each(function () {
            const transportAndPaymentReload = new Shopsys.transportAndPaymentReload.TransportAndPaymentReload($(this));
            transportAndPaymentReload.init();
        });
    });

})(jQuery);

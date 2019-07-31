(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.transportAndPaymentReload = Shopsys.transportAndPaymentReload || {};

    Shopsys.transportAndPaymentReload.TransportAndPaymentReload = function ($transportCountryInput) {

        this.init = function () {
            $transportCountryInput.change(this.onTransportCountryInputChange);
        };

        this.onTransportCountryInputChange = function () {
            Shopsys.ajax({
                url: $transportCountryInput.attr('data-order-transport-and-payment-box-url'),
                dataType: 'html',
                success: function (data) {
                    $('.js-order-box').html(data);
                    Shopsys.register.registerNewContent($('.js-order-box'));
                }
            });
        };

    };

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-transport-country').each(function () {
            var transportAndPaymentReload = new Shopsys.transportAndPaymentReload.TransportAndPaymentReload($(this));
            transportAndPaymentReload.init();
        });
    });

})(jQuery);

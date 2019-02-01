(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.payment = Shopsys.payment || {};

    Shopsys.payment.init = function () {
        var $paymentType = $('.js-payment-type');

        var onPaymentChange = function () {
            var selectedType = $paymentType.val();
            var $goPayPaymentMethodFormLine = $('.js-payment-gopay-payment-method').closest('.form-line');

            if (selectedType === Shopsys.constant('\\Shopsys\\ShopBundle\\Model\\Payment\\Payment::TYPE_GOPAY')) {
                $goPayPaymentMethodFormLine.show();
            } else {
                $goPayPaymentMethodFormLine.hide();
            }
        };

        $paymentType.on('change', onPaymentChange);
        $paymentType.change();
    };

    $(document).ready(function () {
        Shopsys.payment.init();
    });

})(jQuery);

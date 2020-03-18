(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.payment = Shopsys.payment || {};

    Shopsys.payment.init = function () {
        const $goPayType = 'goPay';
        const $paymentType = $('.js-payment-type');
        const $goPayPaymentMethodFormLine = $('.js-payment-gopay-payment-method').closest('.form-line');
        const $activatesGiftCertificateFormLine = $('.js-payment-activates-gift-certificate').closest('.form-line');

        const onPaymentChange = function () {
            const selectedType = $paymentType.val();

            if (selectedType === $goPayType) {
                $goPayPaymentMethodFormLine.show();
                $activatesGiftCertificateFormLine.show();
            } else {
                $goPayPaymentMethodFormLine.hide();
                $activatesGiftCertificateFormLine.hide();
            }
        };

        $paymentType.on('change', onPaymentChange);
        $paymentType.change();
    };

    $(document).ready(function () {
        Shopsys.payment.init();
    });

})(jQuery);

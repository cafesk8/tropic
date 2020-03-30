import Register from 'framework/common/utils/Register';

export default class ChangeOrderPayment {
    onOrderPaymentChoose (event) {
        var $form = event.currentTarget.closest('form');
        $form.submit();
    }

    onOrderGoPayPaymentChoose (event) {
        $('.js-gopay-bank-swifts').toggleClass('display-none', !$(event.currentTarget).is(':checked'));
    }

    static init ($container) {
        $container.filterAllNodes('.js-change-payment-container .form-line').hide();
        var $orderPayments = $container.filterAllNodes('.js-order-change-payment');
        var $orderGoPayPayments = $container.filterAllNodes('.js-gopay-bank-transfer-input');
        const changeOrderPayment = new ChangeOrderPayment();

        $orderPayments.change(changeOrderPayment.onOrderPaymentChoose);
        $orderGoPayPayments.click(changeOrderPayment.onOrderGoPayPaymentChoose);
    }
}

(new Register()).registerCallback(ChangeOrderPayment.init);

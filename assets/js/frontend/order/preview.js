import 'framework/common/components';
import Ajax from 'framework/common/utils/ajax';
import Register from 'framework/common/utils/register';

export default class OrderPreview {

    static loadOrderPreview () {
        const $orderPreview = $('#js-order-preview');
        const $checkedTransport = $('.js-order-transport-input:checked');
        const $checkedPayment = $('.js-order-payment-input:checked');
        const $registrationCheckbox = $('.js-order-registration-checkbox');
        const $orderPreviewCart = $orderPreview.filterAllNodes('#js-order-preview-cart');
        const $orderPreviewItemsList = $orderPreview.filterAllNodes('#js-order-preview-fees');
        const $orderPreviewTotalPrice = $orderPreview.filterAllNodes('#js-order-preview-total-price');
        const data = {};

        if ($checkedTransport.length > 0) {
            data['transportId'] = $checkedTransport.data('id');
        }

        if ($checkedPayment.length > 0) {
            data['paymentId'] = $checkedPayment.data('id');
        }

        if ($registrationCheckbox.length > 0) {
            data['orderStep'] = '3';
            data['registration'] = $registrationCheckbox.is(':checked');
        }

        Ajax.ajaxPendingCall('OrderPreview.loadOrderPreview', {
            loaderElement: '#js-order-preview',
            url: $orderPreview.data('url'),
            type: 'get',
            data: data,
            success: function (successData) {
                const $newOrderPreview = $($.parseHTML(successData));
                $orderPreviewItemsList.html($newOrderPreview.filterAllNodes('#js-order-preview-fees').html());
                $orderPreviewTotalPrice.html($newOrderPreview.filterAllNodes('#js-order-preview-total-price').html());
                $orderPreviewCart.html($newOrderPreview.filterAllNodes('#js-order-preview-cart').html());
                (new Register()).registerNewContent($orderPreview);
            }
        });
    }

    static init ($container) {
        $container
            .filterAllNodes('.js-order-transport-input, .js-order-payment-input, .js-order-registration-checkbox')
            .change(OrderPreview.loadOrderPreview);
    }
}

(new Register()).registerCallback(OrderPreview.init);

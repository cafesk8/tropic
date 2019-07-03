(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.orderPreview = Shopsys.orderPreview || {};

    Shopsys.orderPreview.init = function ($container) {
        $container
            .filterAllNodes('.js-order-transport-input, .js-order-payment-input')
            .change(Shopsys.orderPreview.loadOrderPreview);
    };

    Shopsys.orderPreview.loadOrderPreview = function () {
        var $orderPreview = $('#js-order-preview');
        var $checkedTransport = $('.js-order-transport-input:checked');
        var $checkedPayment = $('.js-order-payment-input:checked');
        var $backButton = $('.js-backButton');
        var $submitButton = $('.js-submitButton');
        var $orderPreviewItemsList = $orderPreview.filterAllNodes('#js-order-preview-fees');
        var $orderPreviewTotalPrice = $orderPreview.filterAllNodes('#js-order-preview-total-price');

        var data = {};
        data['submitButtonText'] = $submitButton.val();
        data['backButtonText'] = $backButton.html();
        data['backButtonLink'] = $backButton.attr('href');

        if ($checkedTransport.length > 0) {
            data['transportId'] = $checkedTransport.data('id');
        }
        if ($checkedPayment.length > 0) {
            data['paymentId'] = $checkedPayment.data('id');
        }

        Shopsys.ajaxPendingCall('Shopsys.orderPreview.loadOrderPreview', {
            loaderElement: '#js-order-preview',
            url: $orderPreview.data('url'),
            type: 'get',
            data: data,
            success: function (data) {
                var $newOrderPreview = $($.parseHTML(data));
                $orderPreviewItemsList.html($newOrderPreview.filterAllNodes('#js-order-preview-fees').html());
                $orderPreviewTotalPrice.html($newOrderPreview.filterAllNodes('#js-order-preview-total-price').html());
                Shopsys.register.registerNewContent($newOrderPreview);
            }
        });
    };

    Shopsys.register.registerCallback(Shopsys.orderPreview.init);

})(jQuery);

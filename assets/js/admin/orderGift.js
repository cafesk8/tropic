import Register from 'framework/common/utils/register';
import Ajax from 'framework/common/utils/ajax';

new Register().registerCallback(function ($container) {
    $container.filterAllNodes('.js-order-gift-domain-id').on('change', function (event) {
        const $target = $(event.currentTarget);
        Ajax.ajax({
            loaderElement: '.js-symbol-after-input',
            type: 'POST',
            data: { domainId: $target.val() },
            url: '/admin/product/order-gift/get-currency-symbol/',
            success: function (data) {
                $('.js-symbol-after-input').text(data['currencySymbol']);
            }
        });
    });
});

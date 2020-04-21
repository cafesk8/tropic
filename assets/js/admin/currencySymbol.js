import Register from 'framework/common/utils/Register';
import Ajax from 'framework/common/utils/Ajax';

new Register().registerCallback(function ($container) {
    $container.filterAllNodes('.js-domain-id-select').on('change', function (event) {
        const $target = $(event.currentTarget);
        Ajax.ajax({
            loaderElement: '.js-symbol-after-input',
            type: 'POST',
            data: { domainId: $target.val() },
            url: '/admin/currency/get-currency-symbol/',
            success: function (data) {
                $('.js-symbol-after-input').text(data['currencySymbol']);
            }
        });
    });
});

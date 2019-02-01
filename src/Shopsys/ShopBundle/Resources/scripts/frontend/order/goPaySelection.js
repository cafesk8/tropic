(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.order = Shopsys.order || {};
    Shopsys.order.goPaySelection = Shopsys.order.goPaySelection || {};

    Shopsys.order.goPaySelection.init = function ($container) {
        var $goPayBankSwiftInputs = $container.filterAllNodes('.js-order-gopay-bank-swift-input');
        $goPayBankSwiftInputs.change(Shopsys.order.goPaySelection.onGoPayBankSwiftChange);
        $goPayBankSwiftInputs.change(Shopsys.order.updateContinueButton);
    };

    Shopsys.order.goPaySelection.onGoPayBankSwiftChange = function () {
        var $this = $(this);
        var isChecked = $this.prop('checked');
        var checkedSwift = $this.data('swift');

        if (isChecked) {
            $('.js-order-gopay-bank-swift-input:checked').each(function (i, checkbox) {
                var $goPayBankSwift = $(this);
                var $checkbox = $(checkbox);
                var swift = $checkbox.data('swift');
                if (swift !== checkedSwift) {
                    $checkbox.prop('checked', false);
                    $goPayBankSwift.closest('label.box-chooser__item').removeClass('box-chooser__item--active');
                }
            });

            var $goPayInput = $('.js-gopay-bank-transfer-input');
            $goPayInput.prop('checked', true);
            $goPayInput.change();

            $('.js-order-payment-input:not(.js-gopay-bank-transfer-input)').each(function () {
                $(this).prop('checked', false);
            });

            $this.closest('label.box-chooser__item').addClass('box-chooser__item--active');
        } else {
            $this.closest('label.box-chooser__item').removeClass('box-chooser__item--active');
        }

        Shopsys.order.updateTransports();
    };

    Shopsys.register.registerCallback(Shopsys.order.goPaySelection.init);

})(jQuery);

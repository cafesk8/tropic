(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.promoCode = Shopsys.promoCode || {};

    Shopsys.promoCode.PromoCode = function ($promoCodeForm) {
        var $unlimitedInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-unlimited');
        var $usageLimitInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-usage-limit');

        var $useNominalDiscountInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-use-nominal-discount');
        var $nominalDiscountInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-nominal-discount');
        var $percentDiscountInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-percent-discount');

        this.init = function () {
            onUnlimitedInputChange();
            onUseNominalDiscountInputChange();

            $unlimitedInput.change(onUnlimitedInputChange);
            $useNominalDiscountInput.change(onUseNominalDiscountInputChange);
        };

        var onUnlimitedInputChange = function () {
            $usageLimitInput.closest('.form-line').toggleClass('display-none', $unlimitedInput.is(':checked'));
        };

        var onUseNominalDiscountInputChange = function () {
            $nominalDiscountInput.closest('.form-line').toggleClass('display-none', $useNominalDiscountInput.is(':checked') === false);
            $percentDiscountInput.closest('.form-line').toggleClass('display-none', $useNominalDiscountInput.is(':checked'));
        };
    };

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-promo-code-form').each(function () {
            var promoCode = new Shopsys.promoCode.PromoCode($(this));
            promoCode.init();
        });
    });

})(jQuery);

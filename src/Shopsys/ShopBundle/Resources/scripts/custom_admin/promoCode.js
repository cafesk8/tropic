(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.promoCode = Shopsys.promoCode || {};

    Shopsys.promoCode.PromoCode = function ($promoCodeForm) {
        var $unlimitedInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-unlimited');
        var $usageLimitInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-usage-limit');

        this.init = function () {
            onUnlimitedInputChange();

            $unlimitedInput.change(onUnlimitedInputChange);
        };

        var onUnlimitedInputChange = function () {
            $usageLimitInput.closest('.form-line').toggleClass('display-none', $unlimitedInput.is(':checked'));
        };
    };

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-promo-code-form').each(function () {
            var promoCode = new Shopsys.promoCode.PromoCode($(this));
            promoCode.init();
        });
    });

})(jQuery);

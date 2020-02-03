(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.promoCode = Shopsys.promoCode || {};

    Shopsys.promoCode.PromoCode = function ($promoCodeForm) {

        var $promoCodeOrCertificateSelect = $promoCodeForm.filterAllNodes('.js-promo-code-promo-code-or-certificate');
        var onlyPromoCodeFiledClassSelector = 'js-promo-code-promo-code-only';

        var $unlimitedInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-unlimited');
        var $usageLimitInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-usage-limit');
        var $limitTypeInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-use-limit-type');

        var $useNominalDiscountInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-use-nominal-discount');
        var $nominalDiscountInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-nominal-discount');
        var $percentDiscountInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-percent-discount');
        var $certificateFieldsInputs = $promoCodeForm.filterAllNodes('.js-promo-code-certificate-field');

        var promoCodeConstant = Shopsys.constant('Shopsys\\ShopBundle\\Model\\Order\\PromoCode\\PromoCodeData::TYPE_PROMO_CODE');
        var limitTypeBrandsConstant = Shopsys.constant('Shopsys\\ShopBundle\\Model\\Order\\PromoCode\\PromoCode::LIMIT_TYPE_BRANDS');
        var limitTypeCategoriesConstant = Shopsys.constant('Shopsys\\ShopBundle\\Model\\Order\\PromoCode\\PromoCode::LIMIT_TYPE_CATEGORIES');
        var limitTypeProductsConstant = Shopsys.constant('Shopsys\\ShopBundle\\Model\\Order\\PromoCode\\PromoCode::LIMIT_TYPE_PRODUCTS');

        this.init = function () {
            onUnlimitedInputChange();
            onUseNominalDiscountInputChange();
            onPromoCodeOrCertificateChange();
            onLimitTypeInputChange();

            $unlimitedInput.change(onUnlimitedInputChange);
            $useNominalDiscountInput.change(onUseNominalDiscountInputChange);
            $promoCodeOrCertificateSelect.change(onPromoCodeOrCertificateChange);
            $limitTypeInput.change(onLimitTypeInputChange);
        };

        var onPromoCodeOrCertificateChange = function () {
            $('.' + onlyPromoCodeFiledClassSelector).closest('.form-line').toggleClass('display-none', $promoCodeOrCertificateSelect.val() !== promoCodeConstant);
            $certificateFieldsInputs.closest('.form-line').toggleClass('display-none', $promoCodeOrCertificateSelect.val() === promoCodeConstant);

            $nominalDiscountInput.closest('.form-line').toggleClass('display-none', $promoCodeOrCertificateSelect.val() !== promoCodeConstant);
            $percentDiscountInput.closest('.form-line').toggleClass('display-none', $promoCodeOrCertificateSelect.val() !== promoCodeConstant);

            if ($promoCodeOrCertificateSelect.val() === promoCodeConstant) {
                onUseNominalDiscountInputChange();
            }
        };

        var onUnlimitedInputChange = function () {
            $usageLimitInput.closest('.form-line').toggleClass('display-none', $unlimitedInput.is(':checked'));
        };

        var onUseNominalDiscountInputChange = function () {
            $nominalDiscountInput.closest('.form-line').toggleClass('display-none', $useNominalDiscountInput.is(':checked') === false);
            $percentDiscountInput.closest('.form-line').toggleClass('display-none', $useNominalDiscountInput.is(':checked'));
        };

        var onLimitTypeInputChange = function () {
            $('.js-promo-code-input-limit-type').closest('.wrap-divider').hide();

            console.log($limitTypeInput.val());

            switch ($limitTypeInput.val()) {
                case limitTypeBrandsConstant:
                    $('.js-promo-code-limit-brands-group').closest('.wrap-divider').show();
                    break;
                case limitTypeCategoriesConstant:
                    $('.js-promo-code-limit-categories-group').closest('.wrap-divider').show();
                    break;
                case limitTypeProductsConstant:
                    $('.js-promo-code-input-limit-products').closest('.wrap-divider').show();
                    break;
            }
        };
    };

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-promo-code-form').each(function () {
            var promoCode = new Shopsys.promoCode.PromoCode($(this));
            promoCode.init();
        });
    });

})(jQuery);

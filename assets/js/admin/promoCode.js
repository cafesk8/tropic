import Register from 'framework/common/utils/register';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.promoCode = Shopsys.promoCode || {};

    Shopsys.promoCode.PromoCode = function ($promoCodeForm) {

        const $promoCodeOrCertificateSelect = $promoCodeForm.filterAllNodes('.js-promo-code-promo-code-or-certificate');
        const onlyPromoCodeFiledClassSelector = 'js-promo-code-promo-code-only';

        const $unlimitedInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-unlimited');
        const $usageLimitInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-usage-limit');
        const $limitTypeInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-use-limit-type');
        const $usageTypeInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-usage-type');

        const $useNominalDiscountInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-use-nominal-discount');
        const $nominalDiscountInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-nominal-discount');
        const $percentDiscountInput = $promoCodeForm.filterAllNodes('.js-promo-code-input-percent-discount');
        const $certificateFieldsInputs = $promoCodeForm.filterAllNodes('.js-promo-code-certificate-field');

        const promoCodeConstant = 'promoCode';
        const limitTypeBrandsConstant = 'brands';
        const limitTypeCategoriesConstant = 'categories';
        const limitTypeProductsConstant = 'products';

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

        const onPromoCodeOrCertificateChange = function () {
            $('.' + onlyPromoCodeFiledClassSelector).closest('.form-line').toggleClass('display-none', $promoCodeOrCertificateSelect.val() !== promoCodeConstant);
            $certificateFieldsInputs.closest('.form-line').toggleClass('display-none', $promoCodeOrCertificateSelect.val() === promoCodeConstant);

            $nominalDiscountInput.closest('.form-line').toggleClass('display-none', $promoCodeOrCertificateSelect.val() !== promoCodeConstant);
            $percentDiscountInput.closest('.form-line').toggleClass('display-none', $promoCodeOrCertificateSelect.val() !== promoCodeConstant);

            if ($promoCodeOrCertificateSelect.val() === promoCodeConstant) {
                onUseNominalDiscountInputChange();
            }
        };

        const onUnlimitedInputChange = function () {
            $usageLimitInput.closest('.form-line').toggleClass('display-none', $unlimitedInput.is(':checked'));
        };

        const onUseNominalDiscountInputChange = function () {
            $nominalDiscountInput.closest('.form-line').toggleClass('display-none', $useNominalDiscountInput.is(':checked') === false);
            $percentDiscountInput.closest('.form-line').toggleClass('display-none', $useNominalDiscountInput.is(':checked'));
        };

        var onLimitTypeInputChange = function () {
            $('.js-promo-code-input-limit-type').closest('.wrap-divider').hide();

            $usageTypeInput.closest('.form-line').removeClass('display-none');

            switch ($limitTypeInput.val()) {
                case limitTypeBrandsConstant:
                    $('.js-promo-code-limit-brands-group').closest('.wrap-divider').show();
                    break;
                case limitTypeCategoriesConstant:
                    $('.js-promo-code-limit-categories-group').closest('.wrap-divider').show();
                    break;
                case limitTypeProductsConstant:
                    $('.js-promo-code-input-limit-products').closest('.wrap-divider').show();
                    $usageTypeInput.closest('.form-line').addClass('display-none');
                    break;
            }
        };
    };

    new Register().registerCallback(function ($container) {
        $container.filterAllNodes('.js-promo-code-form').each(function () {
            const promoCode = new Shopsys.promoCode.PromoCode($(this));
            promoCode.init();
        });
    });

})(jQuery);

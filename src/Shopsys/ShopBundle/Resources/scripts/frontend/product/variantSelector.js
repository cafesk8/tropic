(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.variantSelector = Shopsys.variantSelector || {};

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-product-main-variant-select-button').click(function () {
            if ($(this).hasClass('active')) {
                return;
            }
            var $group = $(this).closest('.js-product-variant-group');
            var variantSelector = '.js-product-detail-' + $(this).data('id');
            var $activeMainVariant = $group.filterAllNodes(variantSelector);

            $group.filterAllNodes('.js-product-detail, .js-detail-availability').addClass('display-none');
            $group.filterAllNodes('.js-product-variant-detail-select-info').removeClass('display-none');
            $group.filterAllNodes('.js-product-variant-select-button').removeClass('active');
            $group.filterAllNodes('.js-product-detail-add-variant-button button[type="submit"]')
                .attr('disabled', 'disabled')
                .addClass('btn--disabled');

            $activeMainVariant.removeClass('display-none');
            $activeMainVariant.filterAllNodes('.js-product-variant-detail').addClass('display-none');
            $activeMainVariant.filterAllNodes('.js-product-variant-first-price').removeClass('display-none');

            $(variantSelector).filterAllNodes('.js-gallery-slides').slick('checkResponsive', false, true);
            Shopsys.productDetail.init($(variantSelector));

            $group.filterAllNodes('.js-lazy-load').each(function () {
                var $lazyloadedImage = $(this);
                Shopsys.lazyLoadInit.manualReplaceSrc($lazyloadedImage);
            });
        });

        $container.filterAllNodes('.js-product-variant-select-button').click(function () {
            if ($(this).hasClass('disabled')) {
                return;
            }
            var $variantDetailWrapper = $(this).closest('.js-product-variant-group').find('.js-product-variant-detail-wrapper');
            var $detailWrap = $(this).closest('.js-product-variant-group').find('.box-detail__wrap');
            var variantDetailSelector = '.js-product-variant-detail-' + $(this).data('id');
            var availabilitySelector = '.js-detail-availability-' + $(this).data('id');

            $variantDetailWrapper.filterAllNodes('.js-product-variant-detail-select-info, .js-product-variant-detail')
                .addClass('display-none');
            $variantDetailWrapper.filterAllNodes(variantDetailSelector).removeClass('display-none');
            $variantDetailWrapper.filterAllNodes('.js-product-detail-add-variant-button button[type="submit"][data-hard-disabled="0"]')
                .removeAttr('disabled')
                .removeClass('btn--disabled');

            $detailWrap.filterAllNodes('.js-product-variant-detail-select-info, .js-detail-availability').addClass('display-none');
            $detailWrap.filterAllNodes(availabilitySelector).removeClass('display-none');

            $container.filterAllNodes('.js-product-variant-select-button').removeClass('active');
            $(this).addClass('active');
        });
    });
})(jQuery);

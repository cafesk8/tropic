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
            $group.filterAllNodes('.js-detail-availability-variant-select-info').removeClass('display-none');
            $group.filterAllNodes('.js-product-variant-select-button').removeClass('active');
            $group.filterAllNodes('.js-product-detail-add-variant-button button[type="submit"]')
                .attr('disabled', 'disabled')
                .addClass('btn--disabled');

            $activeMainVariant.removeClass('display-none');
            $activeMainVariant.filterAllNodes('.js-product-variant-price').addClass('display-none');
            $activeMainVariant.filterAllNodes('.js-product-variant-price:first').removeClass('display-none');

            $(variantSelector).filterAllNodes('.js-gallery-slides').slick('checkResponsive', false, true);
            Shopsys.productDetail.init($(variantSelector));

            $group.filterAllNodes('.js-lazy-load').each(function () {
                var $lazyloadedImage = $(this);
                Shopsys.lazyLoadInit.manualReplaceSrc($lazyloadedImage);
            });
        });

        $container.filterAllNodes('.js-product-variant-select-button').click(function () {
            var $priceWrapper = $(this).closest('.js-product-variant-group').find('.js-product-variant-price-wrapper');
            var $detailWrap = $(this).closest('.js-product-variant-group').find('.box-detail__wrap');
            var variantPriceSelector = '.js-product-variant-price-' + $(this).data('id');
            var availabilitySelector = '.js-detail-availability-' + $(this).data('id');

            $priceWrapper.filterAllNodes('.js-product-variant-price').addClass('display-none');
            $priceWrapper.filterAllNodes(variantPriceSelector).removeClass('display-none');
            $priceWrapper.filterAllNodes('.js-product-detail-add-variant-button button[type="submit"]')
                .removeAttr('disabled')
                .removeClass('btn--disabled');

            $detailWrap.filterAllNodes('.js-detail-availability, .js-detail-availability-variant-select-info').addClass('display-none');
            $detailWrap.filterAllNodes(availabilitySelector).removeClass('display-none');

            $container.filterAllNodes('.js-product-variant-select-button').removeClass('active');
            $(this).addClass('active');
        });
    });
})(jQuery);

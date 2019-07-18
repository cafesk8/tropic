(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.variantSelector = Shopsys.variantSelector || {};

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-product-main-variant-select-button').click(function () {
            var $group = $(this).closest('.js-product-variant-group');
            var variantSelector = '.js-product-detail-' + $(this).data('id');

            $group.filterAllNodes('.js-product-detail').addClass('display-none');
            $group.filterAllNodes(variantSelector).removeClass('display-none');
            $(variantSelector).filterAllNodes('.js-gallery-slides').slick('checkResponsive', false, true);
            Shopsys.productDetail.init($(variantSelector));
        });

        $container.filterAllNodes('.js-product-variant-select-button').click(function () {
            var $priceWrapper = $(this).closest('.js-product-variant-group').find('.js-product-variant-price-wrapper');
            var $detailWrap = $(this).closest('.js-product-variant-group').find('.box-detail__wrap');
            var variantPriceSelector = '.js-product-variant-price-' + $(this).data('id');
            var availabilitySelector = '.js-detail-availability-' + $(this).data('id');

            $priceWrapper.filterAllNodes('.js-product-variant-price').addClass('display-none');
            $priceWrapper.filterAllNodes(variantPriceSelector).removeClass('display-none');

            $detailWrap.filterAllNodes('.js-detail-availability').addClass('display-none');
            $detailWrap.filterAllNodes(availabilitySelector).removeClass('display-none');

            $container.filterAllNodes('.js-product-variant-select-button').removeClass('active');
            $(this).addClass('active');
        });
    });
})(jQuery);

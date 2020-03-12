import ProductDetail from './gallery';
import Register from 'framework/common/utils/Register';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.variantSelector = Shopsys.variantSelector || {};

    new Register().registerCallback(function ($container) {
        $container.filterAllNodes('.js-product-main-variant-select-button').click(function (event) {
            if ($(event.currentTarget).hasClass('active')) {
                return;
            }
            const $group = $(event.currentTarget).closest('.js-product-variant-group');
            const variantSelector = '.js-product-detail-' + $(event.currentTarget).data('id');
            const $activeMainVariant = $group.filterAllNodes(variantSelector);

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
            ProductDetail.init($(variantSelector));
        });

        $container.filterAllNodes('.js-product-variant-select-button').click(function (event) {
            if ($(event.currentTarget).hasClass('disabled')) {
                return;
            }
            const $variantDetailWrapper = $(event.currentTarget).closest('.js-product-variant-group').find('.js-product-variant-detail-wrapper');
            const $detailWrap = $(event.currentTarget).closest('.js-product-variant-group').find('.box-detail__wrap');
            const variantDetailSelector = '.js-product-variant-detail-' + $(event.currentTarget).data('id');
            const availabilitySelector = '.js-detail-availability-' + $(event.currentTarget).data('id');

            $variantDetailWrapper.filterAllNodes('.js-product-variant-detail-select-info, .js-product-variant-detail')
                .addClass('display-none');
            $variantDetailWrapper.filterAllNodes(variantDetailSelector).removeClass('display-none');
            $variantDetailWrapper.filterAllNodes('.js-product-detail-add-variant-button button[type="submit"][data-hard-disabled="0"]')
                .removeAttr('disabled')
                .removeClass('btn--disabled');

            $detailWrap.filterAllNodes('.js-product-variant-detail-select-info, .js-detail-availability').addClass('display-none');
            $detailWrap.filterAllNodes(availabilitySelector).removeClass('display-none');

            $container.filterAllNodes('.js-product-variant-select-button').removeClass('active');
            $(event.currentTarget).addClass('active');
        });
    });
})(jQuery);

(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.variantSelectorToLastVisited = Shopsys.variantSelectorToLastVisited || {};

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-product-main-variant-select-button').click(function () {
            var newLastVisitedProductId = $(this).attr('data-id');
            var lastVisitedCookieName = Shopsys.constant('\\Shopsys\\ShopBundle\\Model\\Product\\LastVisitedProducts\\ProductDetailVisitListener::LAST_VISITED_PRODUCTS_COOKIE');
            var lastVisitedCookieString = $.cookie(lastVisitedCookieName);

            var newValueForCookieString = newLastVisitedProductId + Shopsys.constant('\\Shopsys\\ShopBundle\\Model\\Product\\LastVisitedProducts\\LastVisitedProductsFacade::COOKIE_PRODUCT_IDS_DELIMITER') + lastVisitedCookieString;

            $.cookie(
                lastVisitedCookieName,
                newValueForCookieString,
                {
                    path: '/'
                }
            );

        });
    });
})(jQuery);

import 'jquery.cookie';
import Register from 'framework/common/utils/register';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.variantSelectorToLastVisited = Shopsys.variantSelectorToLastVisited || {};

    new Register().registerCallback(function ($container) {
        $container.filterAllNodes('.js-product-main-variant-select-button').click(function (event) {
            const newLastVisitedProductId = $(event.currentTarget).attr('data-id');
            const lastVisitedCookieName = 'lastVisitedProducts';
            const lastVisitedCookieString = $.cookie(lastVisitedCookieName);

            const newValueForCookieString = newLastVisitedProductId + ',' + lastVisitedCookieString;

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

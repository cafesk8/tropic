import Register from 'framework/common/utils/register';

(function ($) {

    /* eslint-disable no-use-before-define */
    const Shopsys = Shopsys || {};
    Shopsys.filterPosition = Shopsys.filterPosition || {};

    const productFilterOpenerSelector = '.js-product-filter-opener';
    const productListPanelSelector = '.js-product-list-panel';
    const windowWidthLimit = 1024;

    Shopsys.filterPosition.init = function ($container) {
        if ($container.find('.js-product-filter-opener').length > 0) {
            $(productFilterOpenerSelector).click(function (e) {
                e.preventDefault();

                $(productListPanelSelector).toggleClass('active');
                $('.js-product-filter').toggleClass('active-mobile');
                Shopsys.filterPosition.setFilterPosition();
            });

            $(window).resize(function () {
                Shopsys.filterPosition.setFilterPosition();
            });
        }
    };

    Shopsys.filterPosition.setFilterPosition = function () {
        let newPosition = 0;
        const position = $('.js-product-list').position();

        if ($(window).width() < windowWidthLimit && position !== undefined && position.top !== undefined) {
            newPosition = position.top;
        }

        $(productListPanelSelector).css({ 'top': newPosition });
    };

    new Register().registerCallback(Shopsys.filterPosition.init);

})(jQuery);

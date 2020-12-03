import Register from 'framework/common/utils/Register';
import Translator from 'bazinga-translator';

(function ($) {

    /* eslint-disable no-use-before-define */
    const Shopsys = Shopsys || {};
    Shopsys.filterPosition = Shopsys.filterPosition || {};

    const productFilterSelector = '.js-product-filter';
    const productFilterOpenerSelector = '.js-product-filter-opener';
    const productListPanelSelector = '.js-product-list-panel';
    const productFilterDisplayResultSelector = '.js-product-filter-display-result';
    const windowWidthLimit = 1024;

    Shopsys.filterPosition.init = function ($container) {
        if ($container.find('.js-product-filter-opener').length > 0) {
            $(productFilterOpenerSelector).click(function (e) {
                e.preventDefault();

                $(productListPanelSelector).toggleClass('active');
                $(productFilterSelector).toggleClass('active-mobile');
                Shopsys.filterPosition.setFilterPosition();
                Shopsys.filterPosition.setFilterButtons();
            });

            $(window).resize(function () {
                Shopsys.filterPosition.setFilterPosition();
            });

            $(productFilterDisplayResultSelector).click(function (e) {
                $(productListPanelSelector).removeClass('active');
                $(productFilterSelector).removeClass('active-mobile');
                const $productList = $('.js-product-list-ajax-filter-products-with-controls');

                const $page = $('html, body');
                $page.on('scroll mousedown wheel DOMMouseScroll mousewheel keyup touchmove', function () {
                    $page.stop();
                });
                $page.animate({ scrollTop: $productList.offset().top }, 'slow');
                Shopsys.filterPosition.setFilterButtons();
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

    Shopsys.filterPosition.setFilterButtons = function () {
        if ($(productListPanelSelector).hasClass('active')) {
            $(productFilterOpenerSelector).text(Translator.trans('Skrýt filtrování'));
            $(productFilterOpenerSelector).addClass('active');
        } else {
            $(productFilterOpenerSelector).text(Translator.trans('Filtrovat produkty'));
            $(productFilterOpenerSelector).removeClass('active');
        }
    };

    new Register().registerCallback(Shopsys.filterPosition.init);

})(jQuery);

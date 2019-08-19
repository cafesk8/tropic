(function ($) {

    Shopsys = Shopsys || {};
    Shopsys.filterPosition = Shopsys.filterPosition || {};

    var productFilterOpenerSelector = '.js-product-filter-opener';
    var productListPanelSelector = '.js-product-list-panel';
    var windowWidthLimit = 1024;

    Shopsys.filterPosition.init = function ($container) {
        $(productFilterOpenerSelector).click(function (e) {
            e.preventDefault();

            $(productListPanelSelector).toggleClass('active');
            $('.js-product-filter').toggleClass('active-mobile');
            Shopsys.filterPosition.setFilterPosition();
        });

        $(window).resize(function () {
            Shopsys.filterPosition.setFilterPosition();
        });
    };

    Shopsys.filterPosition.setFilterPosition = function () {
        var newPosition = 0;
        var position = $('.js-product-list');

        if ($(window).width() < windowWidthLimit && position !== undefined && position.top !== undefined) {
            newPosition = position.top;
        }

        $(productListPanelSelector).css({ 'top': newPosition });
    };

    Shopsys.register.registerCallback(Shopsys.filterPosition.init);

})(jQuery);

(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.lazyLoadInit = Shopsys.lazyLoadInit || {};

    Shopsys.lazyLoadInit.init = function ($container) {
        $container.filterAllNodes('.js-lazy-load').lazyload();

        $('.js-list-products-item, .js-dropdown').on('hover', function () {
            $(this).filterAllNodes('.js-lazy-load').each(function () {
                var $lazyloadedImage = $(this);
                Shopsys.lazyLoadInit.manualReplaceSrc($lazyloadedImage);
            });
        });
    };

    Shopsys.lazyLoadInit.manualReplaceSrc = function ($lazyloadedImage) {
        $lazyloadedImage.attr('src', $lazyloadedImage.attr('data-original'));
        $lazyloadedImage.removeClass('js-lazy-load');
    };

    Shopsys.register.registerCallback(Shopsys.lazyLoadInit.init);

})(jQuery);

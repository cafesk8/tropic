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

        $('.js-horizontal-list-action-next').click(function () {
            // Classic lazy loading is needed, so I can't use native Slick lazy loading
            $('body').scroll();
        });

        $(window).scroll(function () {
            var positionTop = ($('.js-instagram-scroll-point').offset().top);
            var elementHeight = $('.js-instagram-scroll-point').outerHeight();
            var windowHeight = $(window).height();
            var windowScroll = $(this).scrollTop() + 1000;

            if (windowScroll > (positionTop + elementHeight - windowHeight)) {
                $container.filterAllNodes('.js-lazy-load-on-background').each(function () {
                    var $lazyloadedImage = $(this);
                    Shopsys.lazyLoadInit.manualReplaceBackgroundImage($lazyloadedImage);
                });
            }
        });
    };

    Shopsys.lazyLoadInit.manualReplaceSrc = function ($lazyloadedImage) {
        $lazyloadedImage.attr('src', $lazyloadedImage.attr('data-original'));
        $lazyloadedImage.removeClass('js-lazy-load');
    };

    Shopsys.lazyLoadInit.manualReplaceBackgroundImage = function ($lazyloadedImage) {
        $lazyloadedImage.css('background', 'url(' + $lazyloadedImage.attr('data-original') + ')');
        $lazyloadedImage.removeClass('js-lazy-load-on-background');
    };

    Shopsys.register.registerCallback(Shopsys.lazyLoadInit.init);

})(jQuery);

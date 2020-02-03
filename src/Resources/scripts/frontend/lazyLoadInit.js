(function ($) {
    /* eslint-disable no-new */
    new MiniLazyload({
        rootMargin: '500px',
        threshold: 0.5,
        placeholder: '/assets/frontend/images/noimage.png'
    }, '', MiniLazyload.IGNORE_NATIVE_LAZYLOAD);

    Shopsys = window.Shopsys || {};
    Shopsys.lazyLoadCall = Shopsys.lazyLoadCall || {};

    Shopsys.lazyLoadCall.inContainer = function (container) {
        $(container).find('[loading=lazy]').each(function () {
            $(this).attr('src', $(this).data('src')).addClass('loaded');
        });
        $(window).scroll(function () {
            var positionTop = ($('.js-instagram-scroll-point').offset().top);
            var elementHeight = $('.js-instagram-scroll-point').outerHeight();
            var windowHeight = $(window).height();
            var windowScroll = $(this).scrollTop() + 1000;

            if (windowScroll > (positionTop + elementHeight - windowHeight)) {
                $(container).filterAllNodes('.js-lazy-load-on-background').each(function () {
                    var $lazyloadedImage = $(this);
                    Shopsys.lazyLoadCall.manualReplaceBackgroundImage($lazyloadedImage);
                });
            }
        });
    };

    Shopsys.lazyLoadCall.manualReplaceBackgroundImage = function ($lazyloadedImage) {
        $lazyloadedImage.css('background', 'url(' + $lazyloadedImage.attr('data-original') + ')');
        $lazyloadedImage.removeClass('js-lazy-load-on-background');
    };

    Shopsys.register.registerCallback(Shopsys.lazyLoadCall.inContainer);

})(jQuery);

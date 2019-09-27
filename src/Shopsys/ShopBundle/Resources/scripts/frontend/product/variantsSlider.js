(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.variantsSlider = Shopsys.variantsSlider || {};

    Shopsys.variantsSlider.init = function ($container) {
        $container.filterAllNodes('.js-list-products-item').on('hover', function () {
            var $currentGallery = $(this);
            $currentGallery.find('.js-variantsSlider-slides:not(.slick-initialized)').slick({
                dots: false,
                arrows: true,
                slidesToShow: 4,
                slidesToScroll: 1,
                lazyLoad: 'ondemand',
                infinite: false,
                prevArrow: $currentGallery.filterAllNodes('.js-variantsSlider-action-prev'),
                nextArrow: $currentGallery.filterAllNodes('.js-variantsSlider-action-next')
            });

            $currentGallery.filterAllNodes('.js-variantsSlider-action-prev').click(function (e) {
                e.preventDefault();
            });
            $currentGallery.filterAllNodes('.js-variantsSlider-action-next').click(function (e) {
                e.preventDefault();
            });
        });

    };

    Shopsys.register.registerCallback(Shopsys.variantsSlider.init);

    $(window).resize(function () {
        Shopsys.timeout.setTimeoutAndClearPrevious('Shopsys.variantsSlider.init', Shopsys.variantsSlider.init($(this)), 200);
    });

})(jQuery);

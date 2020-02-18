import Register from 'framework/common/utils/register';
import Timeout from 'framework/common/utils/timeout';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.variantsSlider = Shopsys.variantsSlider || {};

    Shopsys.variantsSlider.init = function ($container) {
        $container.filterAllNodes('.js-list-products-item').on('mouseenter', function (event) {
            const $currentGallery = $(event.currentTarget);
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

    new Register().registerCallback(Shopsys.variantsSlider.init);

    $(window).resize(function (event) {
        Timeout.setTimeoutAndClearPrevious('Shopsys.variantsSlider.init', Shopsys.variantsSlider.init($(event.currentTarget)), 200);
    });

})(jQuery);

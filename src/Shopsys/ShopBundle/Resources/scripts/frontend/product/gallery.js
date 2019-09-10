(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.responsive = Shopsys.responsive || {};
    Shopsys.productDetail = Shopsys.productDetail || {};

    Shopsys.productDetail.init = function ($container) {
        $container.filterAllNodes('.js-gallery-main-image').click(function (event) {
            var $slides = $container.filterAllNodes('.js-gallery .js-gallery-slide-link');
            $slides.filter(':first').trigger('click', event);

            return false;
        });

        var $gallery = $container.filterAllNodes('.js-gallery');

        $gallery.magnificPopup({
            type: 'image',
            delegate: '.js-gallery-slide-link',
            gallery: {
                enabled: true,
                navigateByImgClick: true,
                preload: [0, 1]
            }
        });

        // show more button
        $gallery.filterAllNodes('.js-gallery-item-more').on('click', function (e) {
            e.preventDefault();
            $('.js-gallery-item-more').addClass('display-none');
            $gallery.filterAllNodes('.js-gallery-item').removeClass('display-none');
            $gallery.filterAllNodes('.js-lazy-load').each(function () {
                var $lazyloadedImage = $(this);
                Shopsys.lazyLoadInit.manualReplaceSrc($lazyloadedImage);
            });
        });

        $gallery.filterAllNodes('.js-gallery-slides').slick({
            dots: false,
            arrows: true,
            slidesToShow: 2,
            slidesToScroll: 1,
            lazyLoad: 'ondemand',
            mobileFirst: true,
            infinite: false,
            prevArrow: $gallery.filterAllNodes('.js-gallery-prev'),
            nextArrow: $gallery.filterAllNodes('.js-gallery-next'),
            responsive: [
                {
                    breakpoint: Shopsys.responsive.XS,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 2
                    }
                },
                {
                    breakpoint: Shopsys.responsive.MD,
                    settings: {
                        slidesToShow: 4,
                        slidesToScroll: 3
                    }
                },
                {
                    breakpoint: Shopsys.responsive.LG,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 2
                    }
                },
                {
                    breakpoint: Shopsys.responsive.VL,
                    settings: {
                        slidesToShow: 4,
                        slidesToScroll: 3
                    }
                }
            ]
        });
    };

    $(document).ready(function () {
        Shopsys.productDetail.init($('body'));
    });

})(jQuery);

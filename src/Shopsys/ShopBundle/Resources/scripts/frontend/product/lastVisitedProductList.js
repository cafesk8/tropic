(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.horizontalProuductList = Shopsys.horizontalProuductList || {};

    Shopsys.horizontalProuductList.init = function ($container) {
        $container.filterAllNodes('.js-list-products').each(function () {
            var $currentGallery = $(this);

            $currentGallery.find('.js-list-products-slides').slick({
                dots: false,
                arrows: true,
                slidesToShow: 1,
                slidesToScroll: 1,
                lazyLoad: 'ondemand',
                mobileFirst: true,
                infinite: false,
                prevArrow: $currentGallery.filterAllNodes('.js-list-products-prev'),
                nextArrow: $currentGallery.filterAllNodes('.js-list-products-next'),
                responsive: [
                    {
                        breakpoint: Shopsys.responsive.SM,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: 680,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: Shopsys.responsive.LG,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: 860,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: Shopsys.responsive.VL,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: Shopsys.responsive.XL,
                        settings: {
                            slidesToShow: 6,
                            slidesToScroll: 1
                        }
                    }
                ]
            });
        });
    };

    Shopsys.register.registerCallback(Shopsys.horizontalProuductList.init);

    $(window).resize(function () {
        Shopsys.timeout.setTimeoutAndClearPrevious('Shopsys.horizontalProuductList.init', Shopsys.horizontalProuductList.init, 200);
    });

})(jQuery);

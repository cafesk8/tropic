(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.topProductsList = Shopsys.topProductsList || {};

    Shopsys.topProductsList.init = function ($container) {
        $container.filterAllNodes('.js-list-top-products').each(function () {
            var $currentTopProducts = $(this);

            $currentTopProducts.find('.js-list-top-products-slides').slick({
                dots: false,
                arrows: true,
                slidesToShow: 1,
                slidesToScroll: 1,
                lazyLoad: 'ondemand',
                mobileFirst: true,
                infinite: false,
                prevArrow: $currentTopProducts.filterAllNodes('.js-list-top-products-prev'),
                nextArrow: $currentTopProducts.filterAllNodes('.js-list-top-products-next'),
                responsive: [
                    {
                        breakpoint: Shopsys.responsive.SM,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: 680,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: Shopsys.responsive.LG,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: 860,
                        settings: {
                            slidesToShow: 3,
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
                            slidesToShow: 4,
                            slidesToScroll: 1
                        }
                    }
                ]
            });
        });
    };

    Shopsys.register.registerCallback(Shopsys.topProductsList.init);

    $(window).resize(function () {
        Shopsys.timeout.setTimeoutAndClearPrevious('Shopsys.topProductsList.init', Shopsys.topProductsList.init, 200);
    });

})(jQuery);

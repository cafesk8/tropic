(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.horizontalList = Shopsys.horizontalList || {};

    Shopsys.horizontalList.init = function ($container) {
        $container.filterAllNodes('.js-horizontal-list').each(function () {
            var $currentGallery = $(this);
            var galleryType = $currentGallery.data('type');

            var types = {
                'last-visited': [1, 2, 3, 3, 6],
                'top-products': [1, 2, 3, 3, 4],
                'products': [2, 3, 2, 3, 6],
                'references': [1, 2, 3, 3, 4],
                'accessories-products': [1, 2, 3, 3, 4],
                'basket-additionals': [1, 2, 3, 3, 3] // TODO COD (i dont exactly know how many products i have to set)
            };

            var selectedType = types[galleryType];

            $currentGallery.find('.js-horizontal-list-slides').slick({
                dots: false,
                arrows: true,
                slidesToShow: 1,
                slidesToScroll: 1,
                lazyLoad: 'ondemand',
                mobileFirst: true,
                infinite: false,
                prevArrow: $currentGallery.filterAllNodes('.js-horizontal-list-action-prev'),
                nextArrow: $currentGallery.filterAllNodes('.js-horizontal-list-action-next'),
                responsive: [
                    {
                        breakpoint: Shopsys.responsive.SM,
                        settings: {
                            slidesToShow: selectedType[0],
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: Shopsys.responsive.MD,
                        settings: {
                            slidesToShow: selectedType[1],
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: Shopsys.responsive.LG,
                        settings: {
                            slidesToShow: selectedType[2],
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: Shopsys.responsive.VL,
                        settings: {
                            slidesToShow: selectedType[3],
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: Shopsys.responsive.XL,
                        settings: {
                            slidesToShow: selectedType[4],
                            slidesToScroll: 1
                        }
                    }
                ]
            });
        });
    };

    Shopsys.register.registerCallback(Shopsys.horizontalList.init);

    $(window).resize(function () {
        Shopsys.timeout.setTimeoutAndClearPrevious('Shopsys.horizontalList.init', Shopsys.horizontalList.init, 200);
    });

})(jQuery);

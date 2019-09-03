(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.horizontalList = Shopsys.horizontalList || {};

    Shopsys.horizontalList.init = function ($container) {
        $container.filterAllNodes('.js-horizontal-list').each(function () {
            var $currentGallery = $(this);
            var galleryType = $currentGallery.data('type');
            var prevArrow = $currentGallery.filterAllNodes('.js-horizontal-list-action-prev');
            var nextArrow = $currentGallery.filterAllNodes('.js-horizontal-list-action-next');

            var types = {
                'last-visited': [1, 2, 3, 3, 6],
                'top-products': [1, 2, 3, 3, 4],
                'products': [2, 3, 2, 3, 6],
                'references': [1, 2, 3, 3, 4],
                'accessories-products': [1, 2, 3, 3, 4],
                'basket-additionals': [1, 2, 3, 3, 3]
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
                prevArrow: prevArrow,
                nextArrow: nextArrow,
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

            $currentGallery.find('.js-horizontal-list-slides').on('breakpoint', function (event, slick, breakpoint) {
                recalculateSlickArrowVisibility(breakpoint);
            });
            recalculateSlickArrowVisibility(window.innerWidth || $(window).width());

            function recalculateSlickArrowVisibility (breakpoint) {
                switch (true) {
                    case breakpoint === Shopsys.responsive.SM:
                        toggleSlickArrow(selectedType[0]);
                        break;
                    case breakpoint === Shopsys.responsive.MD:
                        toggleSlickArrow(selectedType[1]);
                        break;
                    case breakpoint === Shopsys.responsive.LG:
                        toggleSlickArrow(selectedType[2]);
                        break;
                    case breakpoint === Shopsys.responsive.VL:
                        toggleSlickArrow(selectedType[3]);
                        break;
                    case breakpoint >= Shopsys.responsive.XL:
                        toggleSlickArrow(selectedType[4]);
                        break;
                    default:
                        toggleSlickArrow(1);
                        break;
                }
            }

            function toggleSlickArrow (minCountForShowArrows) {
                var countOfProduct = $currentGallery.data('count-of-product');

                if (countOfProduct > minCountForShowArrows) {
                    prevArrow.show();
                    nextArrow.show();
                } else {
                    prevArrow.hide();
                    nextArrow.hide();
                }
            }
        });
    };

    Shopsys.register.registerCallback(Shopsys.horizontalList.init);

    $(window).resize(function () {
        Shopsys.timeout.setTimeoutAndClearPrevious('Shopsys.horizontalList.init', Shopsys.horizontalList.init, 200);
    });

})(jQuery);

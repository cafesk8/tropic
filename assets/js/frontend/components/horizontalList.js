import Register from 'framework/common/utils/Register';
import Responsive from '../utils/responsive';
import Timeout from 'framework/common/utils/Timeout';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.horizontalList = Shopsys.horizontalList || {};

    Shopsys.horizontalList.init = function ($container) {
        $container.filterAllNodes('.js-horizontal-list').each(function () {
            const $currentGallery = $(this);
            const galleryType = $currentGallery.data('type');
            const prevArrow = $currentGallery.filterAllNodes('.js-horizontal-list-action-prev');
            const nextArrow = $currentGallery.filterAllNodes('.js-horizontal-list-action-next');

            const dotsTypes = {
                'last-visited': [false, false, true, true, true, true],
                'top-products': [false, false, true, true, true, true],
                'accessories-products': [false, false, true, true, true, true],
                'products': [false, false, true, true, true, true],
                'references': [false, false, true, true, true, true],
                'basket-additionals': [false, false, true, true, true, true],
                'products-set': [true, true, true, true, true, true]
            };

            const types = {
                'last-visited': [1, 2, 3, 3, 5],
                'top-products': [1, 2, 3, 4, 4],
                'accessories-products': [1, 2, 3, 4, 6],
                'products': [2, 3, 2, 3, 6],
                'references': [1, 2, 3, 3, 4],
                'basket-additionals': [1, 2, 3, 2, 3],
                'products-set': [1, 2, 3, 2, 3]
            };

            const slideTypes = {
                'last-visited': [1, 2, 3, 3, 5],
                'top-products': [1, 2, 3, 4, 4],
                'accessories-products': [1, 2, 3, 4, 6],
                'products': [2, 3, 2, 3, 6],
                'references': [1, 2, 3, 3, 4],
                'basket-additionals': [1, 2, 3, 2, 3],
                'products-set': [1, 1, 1, 1, 1]
            };

            const selectedDots = dotsTypes[galleryType];
            const selectedType = types[galleryType];
            const selectedSlideTypes = slideTypes[galleryType];

            $currentGallery.find('.js-horizontal-list-slides').slick({
                dots: selectedDots[0],
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
                        breakpoint: Responsive.SM,
                        settings: {
                            slidesToShow: selectedType[0],
                            slidesToScroll: selectedSlideTypes[0],
                            dots: selectedDots[1]
                        }
                    },
                    {
                        breakpoint: Responsive.MD,
                        settings: {
                            slidesToShow: selectedType[1],
                            slidesToScroll: selectedSlideTypes[1],
                            dots: selectedDots[2]
                        }
                    },
                    {
                        breakpoint: Responsive.LG,
                        settings: {
                            slidesToShow: selectedType[2],
                            slidesToScroll: selectedSlideTypes[2],
                            dots: selectedDots[3]
                        }
                    },
                    {
                        breakpoint: Responsive.VL,
                        settings: {
                            slidesToShow: selectedType[3],
                            slidesToScroll: selectedSlideTypes[3],
                            dots: selectedDots[4]
                        }
                    },
                    {
                        breakpoint: Responsive.XL,
                        settings: {
                            slidesToShow: selectedType[4],
                            slidesToScroll: selectedSlideTypes[4],
                            dots: selectedDots[5]
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
                    case breakpoint === Responsive.SM:
                        toggleSlickArrow(selectedType[0]);
                        break;
                    case breakpoint === Responsive.MD:
                        toggleSlickArrow(selectedType[1]);
                        break;
                    case breakpoint === Responsive.LG:
                        toggleSlickArrow(selectedType[2]);
                        break;
                    case breakpoint === Responsive.VL:
                        toggleSlickArrow(selectedType[3]);
                        break;
                    case breakpoint >= Responsive.XL:
                        toggleSlickArrow(selectedType[4]);
                        break;
                    default:
                        toggleSlickArrow(1);
                        break;
                }
            }

            function toggleSlickArrow (minCountForShowArrows) {
                const countOfProduct = $currentGallery.data('count-of-product');

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

    new Register().registerCallback(Shopsys.horizontalList.init);

    $(window).resize(function (event) {
        Timeout.setTimeoutAndClearPrevious('Shopsys.horizontalList.init', Shopsys.horizontalList.init($(event.currentTarget)), 200);
    });

})(jQuery);

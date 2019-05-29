(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.referencesList = Shopsys.referencesList || {};

    Shopsys.referencesList.init = function ($container) {
        $container.filterAllNodes('.js-references').each(function () {
            var $currentReferences = $(this);

            $currentReferences.find('.js-references-slides').slick({
                dots: false,
                arrows: true,
                slidesToShow: 1,
                slidesToScroll: 1,
                lazyLoad: 'ondemand',
                mobileFirst: true,
                infinite: false,
                prevArrow: $currentReferences.filterAllNodes('.js-references-prev'),
                nextArrow: $currentReferences.filterAllNodes('.js-references-next'),
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

    Shopsys.register.registerCallback(Shopsys.referencesList.init);

    $(window).resize(function () {
        Shopsys.timeout.setTimeoutAndClearPrevious('Shopsys.referencesList.init', Shopsys.referencesList.init, 200);
    });

})(jQuery);

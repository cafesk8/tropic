import '../components/filterAllNodes';
import 'magnific-popup';
import 'slick-carousel';
import Responsive from '../utils/responsive';
import Register from 'framework/common/utils/Register';

export default class ProductDetail {

    static init ($container) {
        $container.filterAllNodes('.js-gallery-main-image').click(function (event) {
            const $slides = $container.filterAllNodes('.js-gallery .js-gallery-slide-link');
            $slides.filter(':first').trigger('click', event);

            return false;
        });

        const $gallery = $container.filterAllNodes('.js-gallery');

        if ($gallery.length === 0) {
            return;
        }

        $gallery.magnificPopup({
            type: 'image',
            delegate: '.js-gallery-slide-link',
            gallery: {
                enabled: true,
                navigateByImgClick: true,
                preload: [0, 1],
                tCounter: '%curr% z %total%'
            }
        });

        // show more button
        $gallery.filterAllNodes('.js-gallery-item-more').on('click', function (e) {
            e.preventDefault();
            $('.js-gallery-item-more').addClass('display-none');
            $gallery.filterAllNodes('.js-gallery-item').removeClass('display-none');
        });

        const $sliders = $gallery.filterAllNodes('.js-gallery-slides');
        $sliders.not('.slick-initialized').slick({
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
                    breakpoint: Responsive.XS,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 2
                    }
                },
                {
                    breakpoint: Responsive.MD,
                    settings: {
                        slidesToShow: 4,
                        slidesToScroll: 3
                    }
                },
                {
                    breakpoint: Responsive.LG,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 2
                    }
                },
                {
                    breakpoint: Responsive.VL,
                    settings: {
                        slidesToShow: 4,
                        slidesToScroll: 3
                    }
                }
            ]
        });
    }
}

new Register().registerCallback(ProductDetail.init);

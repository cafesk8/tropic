import Register from 'framework/common/utils/Register';

export default class HoverImage {
    static init ($container) {
        var $image = $container.filterAllNodes('.js-hover-image');

        $image.mouseenter(function () {
            var $bigImageSrc = $(this).data('original-image');
            var $imageExist = $(this).find('.js-hover-image-original').length > 0;

            if ($imageExist) {
                $(this).find('.js-hover-image-original').removeClass('display-none');
            } else {
                $(this).append('<div class="list-variants__item__description__image-original js-hover-image-original"><img src="' + $bigImageSrc + '"></div>');
            };
        });

        $image.mouseleave(function () {
            $(this).find('.js-hover-image-original').addClass('display-none');
        });
    }
}

new Register().registerCallback(HoverImage.init);

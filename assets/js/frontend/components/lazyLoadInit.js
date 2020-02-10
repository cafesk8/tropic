import MiniLazyload from 'minilazyload';
import Register from 'framework/common/utils/register';

/* eslint-disable no-new */
new MiniLazyload({
    rootMargin: '500px',
    threshold: 0.5,
    placeholder: '/assets/frontend/images/noimage.png'
}, '', MiniLazyload.IGNORE_NATIVE_LAZYLOAD);

export function lazyLoadCall (container) {
    $(container).find('[loading=lazy]').each(function () {
        $(this).attr('src', $(this).data('src')).addClass('loaded');
    });
}

export function manualReplaceBackgroundImage ($lazyloadedImage) {
    $lazyloadedImage.css('background', 'url(' + $lazyloadedImage.attr('data-original') + ')');
    $lazyloadedImage.removeClass('js-lazy-load-on-background');
};

$(window).scroll(function (event) {
    const $instagramScrollPoint = $('.js-instagram-scroll-point');
    if ($instagramScrollPoint.length > 0) {
        const positionTop = $instagramScrollPoint.offset().top;
        const elementHeight = $instagramScrollPoint.outerHeight();
        const windowHeight = $(window).height();
        const windowScroll = $(event.currentTarget).scrollTop() + 1000;

        if (windowScroll > (positionTop + elementHeight - windowHeight)) {
            $('body').filterAllNodes('.js-lazy-load-on-background').each(function () {
                const $lazyloadedImage = $(this);
                manualReplaceBackgroundImage($lazyloadedImage);
            });
        }
    }
});

(new Register()).registerCallback(lazyLoadCall);

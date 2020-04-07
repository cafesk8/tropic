import Register from 'framework/common/utils/Register';

export default function scrollToPosition () {
    const spaceAbovePosition = 20;
    const animationDuration = 1000;

    $('.js-scroll-to-position').click(function () {
        const $target = $($(this).attr('href'));
        const targetPosition = $target.offset().top - spaceAbovePosition;

        $('html, body').animate({
            scrollTop: targetPosition
        }, animationDuration);

        return false;
    });
};

(new Register()).registerCallback(scrollToPosition);

import Register from 'framework/common/utils/Register';

export default function scrollToPosition () {
    const animationDuration = 1000;

    $('.js-scroll-to-position').click(function () {
        const value = $(this).data('space-above-position');
        const spaceAbovePosition = () => {
            if (value !== undefined) {
                return value;
            } else {
                return 20;
            }
        };

        const $target = $($(this).attr('href'));
        const targetPosition = $target.offset().top - spaceAbovePosition();

        $('html, body').animate({
            scrollTop: targetPosition
        }, animationDuration);

        return false;
    });
};

(new Register()).registerCallback(scrollToPosition);

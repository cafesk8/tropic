import Register from 'framework/common/utils/Register';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.variantsToggle = Shopsys.variantsToggle || {};

    Shopsys.variantsToggle.init = function ($container) {
        $container.filterAllNodes('.js-variantsToggle').each(Shopsys.variantsToggle.toggleFunction);
    };

    Shopsys.variantsToggle.toggleFunction = function (event) {
        $('.js-variantsToggle-button').click(function () {
            const container = $(this).closest('.js-variantsToggle').find('.js-variantsToggle-container');
            container.slideToggle();

            if ($(this).hasClass('open')) {
                Shopsys.variantsToggle.scrollToUp();
            }

            $(this).toggleClass('open');
        });
    };

    Shopsys.variantsToggle.scrollToUp = function () {
        const spaceAbovePosition = 60;
        const $target = $('.js-variantsToggle');
        const targetPosition = $target.offset().top - spaceAbovePosition;

        $('html, body').animate({
            scrollTop: targetPosition
        }, 1000);

        return false;
    };

    new Register().registerCallback(Shopsys.variantsToggle.init);

})(jQuery);

import Register from 'framework/common/utils/register';

(function ($) {
    const Shopsys = window.Shopsys || {};

    new Register().registerCallback(function ($container) {
        $container.filterAllNodes('.js-anchor-tabs .js-tabs-button').click(function (e) {
            e.preventDefault();
            const target = $(e.currentTarget).data('tab-id');
            if ($(window).width() > 1024) {
                $('.js-tabs-content[data-tab-id=' + target + ']').each(function () {
                    if ($(this).closest('.box-detail').hasClass('display-none') === false) {
                        $(this).show();
                        $('html, body').animate({
                            scrollTop: $(this).offset().top
                        }, 1000);
                    }
                });
            } else {
                $(this).toggleClass('active');
                $('.js-tabs-content[data-tab-id=' + target + ']').slideToggle();
            }

            return false;
        });
    });

})(jQuery);

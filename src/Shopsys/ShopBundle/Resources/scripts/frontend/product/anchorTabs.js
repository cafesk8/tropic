(function ($) {
    Shopsys = window.Shopsys || {};

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-anchor-tabs .js-tabs-button').click(function (e) {
            e.preventDefault();
            var target = $(this).data('tab-id');
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

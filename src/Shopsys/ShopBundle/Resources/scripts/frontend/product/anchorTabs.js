(function ($) {
    Shopsys = window.Shopsys || {};

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-anchor-tabs .js-tabs-button').click(function (e) {
            e.preventDefault();
            var target = $(this).data('tab-id');

            $('.js-tabs-content[data-tab-id=' + target + ']').each(function () {
                if ($(this).closest('.box-detail').hasClass('display-none') === false) {
                    $('html, body').animate({
                        scrollTop: $(this).offset().top
                    }, 1000);
                }
            });

            return false;
        });
    });

})(jQuery);

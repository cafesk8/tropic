(function ($) {
    Shopsys = window.Shopsys || {};

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-anchor-tabs .js-tabs-button').click(function (e) {
            e.preventDefault();
            var target = $(this).data('tab-id');

            $('html, body').animate({
                scrollTop: $('.js-tabs-content[data-tab-id=' + target + ']').offset().top
            }, 1000);

            return false;
        });
    });

})(jQuery);

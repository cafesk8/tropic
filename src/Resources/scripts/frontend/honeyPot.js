(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.honeyPot = Shopsys.honeyPot || {};

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-honey').parents('.form-line').hide();
        $container.filterAllNodes('.js-honey').hide();
    });

})(jQuery);

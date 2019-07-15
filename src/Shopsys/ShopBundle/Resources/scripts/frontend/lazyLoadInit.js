(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.lazyLoadInit = Shopsys.lazyLoadInit || {};

    Shopsys.lazyLoadInit.init = function ($container) {
        $container.filterAllNodes('.js-lazy-load').lazyload();
    };

    Shopsys.register.registerCallback(Shopsys.lazyLoadInit.init);

})(jQuery);

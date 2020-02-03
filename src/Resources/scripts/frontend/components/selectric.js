(function ($) {
    Shopsys = window.Shopsys || { };
    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('select').selectric({
            arrowButtonMarkup: '<b class="button"><i class="svg svg-arrow-thin"></i></b>'
        });
    });
})(jQuery);

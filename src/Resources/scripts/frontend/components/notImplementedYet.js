(function ($) {

    Shopsys = window.Shopsys || {};

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-not-implemented-yet')
            .attr('title', Shopsys.translator.trans('Ještě nebylo implementováno.'))
            .tooltip();
    });

})(jQuery);

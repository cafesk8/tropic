(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.lazyLoadInit = Shopsys.lazyLoadInit || {};

    Shopsys.lazyLoadInit.init = function () {
        $('.js-lazy-load').lazyload();
    };

    $(document).ready(function () {
        Shopsys.lazyLoadInit.init();
    });

})(jQuery);

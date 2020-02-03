(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.cartBox = Shopsys.cartBox || {};

    Shopsys.cartBox.init = function ($container) {
        $container.filterAllNodes('#js-cart-box').bind('reload', Shopsys.cartBox.reload);

        $container.filterAllNodes('.js-cart-ajax-remove').click(function () {
            Shopsys.ajax({
                loaderElement: '.js-cart-box-content',
                url: $(this).data('src'),
                type: 'post',
                success: function (html) {
                    var $html = $($.parseHTML(html));

                    var $cartBox = $html.filterAllNodes('#js-cart-box');
                    $cartBox.filterAllNodes('.js-hover-intent').addClass('open');
                    $('#js-cart-box').replaceWith($cartBox);

                    Shopsys.register.registerNewContent($cartBox);
                }
            });
        });
    };

    Shopsys.cartBox.reload = function (event) {

        Shopsys.ajax({
            loaderElement: '#js-cart-box',
            url: $(this).data('reload-url'),
            type: 'get',
            success: function (data) {
                $('#js-cart-box').replaceWith(data);

                Shopsys.register.registerNewContent($('#js-cart-box').parent());
            }
        });

        event.preventDefault();
    };

    Shopsys.register.registerCallback(Shopsys.cartBox.init);

})(jQuery);

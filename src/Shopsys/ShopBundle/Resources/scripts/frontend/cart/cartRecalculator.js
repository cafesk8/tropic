(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.cartRecalculator = Shopsys.cartRecalculator || {};

    Shopsys.cartRecalculator.init = function ($container) {
        function reloadWithDelay (delay) {
            Shopsys.timeout.setTimeoutAndClearPrevious(
                'cartRecalculator',
                function () {
                    Shopsys.cartRecalculator.reload();
                },
                delay
            );
        }

        // reload content after delay when clicking +/-
        $container.filterAllNodes('.js-cart-item .js-spinbox-plus, .js-cart-item .js-spinbox-minus').click(
            function (event) {
                reloadWithDelay(1000);
                event.preventDefault();
            }
        );

        // reload content after delay after leaving input or pressing ENTER
        // but only if value was changed
        $container.filterAllNodes('.js-cart-item .js-spinbox-input')
            .change(function () {
                $(this).blur(function () {
                    reloadWithDelay(1000);
                });
            })
            .keydown(function (event) {
                if (event.keyCode === Shopsys.keyCodes.ENTER) {
                    reloadWithDelay(0);
                    event.preventDefault();
                }
            });

        $container.filterAllNodes('.js-take-gift')
            .change(function () {
                var isCurrentChecked = $(this).is(':checked');
                var cartItemProductId = $(this).attr('data-cart-product-id');
                $('.js-take-gift').each(function () {
                    if ($(this).attr('data-cart-product-id') === cartItemProductId) {
                        $(this).prop('checked', false);
                    }
                });

                $(this).prop('checked', isCurrentChecked);

                reloadWithDelay(1000);
            });

        $container.filterAllNodes('.js-cart-take-promo-product')
            .change(function () {
                var isCurrentChecked = $(this).is(':checked');
                var cartItemPromoProductId = $(this).attr('data-cart-promo-product-id');
                $('.js-cart-take-promo-product').each(function () {
                    if ($(this).attr('data-cart-promo-product-id') !== cartItemPromoProductId) {
                        $(this).prop('checked', false);
                    }
                });

                $(this).prop('checked', isCurrentChecked);

                reloadWithDelay(1000);
            });
    };

    Shopsys.cartRecalculator.reload = function () {
        var formData = $('.js-cart-form').serializeArray();
        formData.push({
            name: Shopsys.constant('\\Shopsys\\ShopBundle\\Controller\\Front\\CartController::RECALCULATE_ONLY_PARAMETER_NAME'),
            value: 1
        });

        Shopsys.ajax({
            overlayDelay: 0, // show loader immediately to avoid clicking during AJAX request
            loaderElement: '.js-main-content',
            url: $('.js-cart-form').attr('action'),
            type: 'post',
            data: formData,
            dataType: 'html',
            success: function (html) {
                var $html = $($.parseHTML(html));

                var $mainContent = $html.find('.js-main-content');
                var $cartBox = $html.find('#js-cart-box');

                $('.js-main-content').replaceWith($mainContent);
                $('#js-cart-box').replaceWith($cartBox);

                Shopsys.register.registerNewContent($mainContent);
                Shopsys.register.registerNewContent($cartBox);
            }
        });
    };

    Shopsys.register.registerCallback(Shopsys.cartRecalculator.init);

})(jQuery);

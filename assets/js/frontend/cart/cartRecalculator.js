import Ajax from 'framework/common/utils/Ajax';
import Timeout from 'framework/common/utils/Timeout';
import Register from 'framework/common/utils/Register';
import constant from '../utils/constant';
import { KeyCodes } from 'framework/common/utils/KeyCodes';

export default class CartRecalculator {

    constructor ($container) {
        const _this = this;

        // reload content after delay when clicking +/-
        $container.filterAllNodes('.js-cart-item .js-spinbox-plus, .js-cart-item .js-spinbox-minus').click(
            function (event) {
                const $currentTarget = $(event.currentTarget);
                const $warning = $currentTarget.closest('.js-cart-item').find('.js-maximum-amount-warning');

                if ($currentTarget.data('disabled') === 'disabled' && $warning.is(':visible')) {
                    return false;
                }

                _this.reloadWithDelay(1000, _this);
                event.preventDefault();
            }
        );

        // reload content after delay after leaving input or pressing ENTER
        // but only if value was changed
        $container.filterAllNodes('.js-cart-item .js-spinbox-input')
            .change(function () {
                $(this).blur(function () {
                    _this.reloadWithDelay(1000, _this);
                });
            })
            .keydown(function (event) {
                if (event.keyCode === KeyCodes.ENTER) {
                    _this.reloadWithDelay(0, _this);
                    event.preventDefault();
                }
            });

        $container.filterAllNodes('.js-take-gift')
            .change(function () {
                const isCurrentChecked = $(this).is(':checked');
                const cartItemProductId = $(this).attr('data-cart-product-id');
                $('.js-take-gift').each(function () {
                    if ($(this).attr('data-cart-product-id') === cartItemProductId) {
                        $(this).prop('checked', false);
                    }
                });

                $(this).prop('checked', isCurrentChecked);

                _this.reloadWithDelay(1000, _this);
            });
    }

    reload () {
        console.log('reload');
        const formData = $('.js-cart-form').serializeArray();
        formData.push({
            name: constant('\\App\\Controller\\Front\\CartController::RECALCULATE_ONLY_PARAMETER_NAME'),
            value: 1
        });

        Ajax.ajax({
            overlayDelay: 0, // show loader immediately to avoid clicking during AJAX request
            loaderElement: '.js-main-content',
            url: $('.js-cart-form').attr('action'),
            type: 'post',
            data: formData,
            dataType: 'html',
            success: function (html) {
                const $html = $($.parseHTML(html));

                const $mainContent = $html.find('.js-main-content');
                const $cartBox = $html.find('#js-cart-box');

                $('.js-main-content').replaceWith($mainContent);
                $('#js-cart-box').replaceWith($cartBox);

                (new Register()).registerNewContent($mainContent);
                (new Register()).registerNewContent($cartBox);
            }
        });
    }

    reloadWithDelay (delay, cartRecalculator) {
        console.log('delay');
        Timeout.setTimeoutAndClearPrevious(
            'cartRecalculator',
            function () {
                cartRecalculator.reload();
            },
            delay
        );
    }

    static init ($container) {
        // eslint-disable-next-line no-new
        new CartRecalculator($container);
    }

}

(new Register()).registerCallback(CartRecalculator.init);

import Ajax from 'framework/common/utils/Ajax';
import Register from 'framework/common/utils/Register';

export default class CartBox {
    static reload (event) {
        Ajax.ajax({
            loaderElement: '#js-cart-box',
            url: $(event.currentTarget).data('reload-url'),
            data: { 'isIntentActive': $(event.currentTarget).hasClass('active'), loadItems: true },
            type: 'get',
            success: function (data) {
                $('#js-cart-box').replaceWith(data);

                (new Register()).registerNewContent($('#js-cart-box').parent());
            }
        });

        event.preventDefault();
    }

    static removeItem (event) {
        Ajax.ajax({
            loaderElement: '.js-cart-box-content',
            url: $(event.currentTarget).data('src'),
            type: 'post',
            success: function (html) {
                const $html = $($.parseHTML(html));

                const $cartBox = $html.filterAllNodes('#js-cart-box');
                $cartBox.filterAllNodes('.js-hover-intent').addClass('open');
                $('#js-cart-box').replaceWith($cartBox);

                (new Register()).registerNewContent($cartBox);
            }
        });
    }

    static init ($container) {
        $container.filterAllNodes('#js-cart-box').on('reload', CartBox.reload);
        $container.filterAllNodes('.js-cart-ajax-remove').on('click', CartBox.removeItem);
    }
}

(new Register()).registerCallback(CartBox.init);

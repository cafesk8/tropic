import 'framework/common/components';
import Ajax from 'framework/common/utils/Ajax';
import Register from 'framework/common/utils/Register';
import Window from '../utils/Window';
import Translator from 'bazinga-translator';

export default class AddProduct {
    static ajaxSubmit (event) {
        Ajax.ajax({
            url: $(event.target).data('ajax-url'),
            type: 'POST',
            data: $(event.target).serialize(),
            dataType: 'html',
            success: AddProduct.onSuccess,
            error: AddProduct.onError
        });

        event.preventDefault();
    }

    static onSuccess (data, textStatus, request) {
        if (request.getResponseHeader('Content-Type').indexOf('json') > -1) {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
            location.reload();
            return;
        }

        const buttonContinueUrl = $($.parseHTML(data)).filterAllNodes('.js-add-product-url-cart').data('url');
        const isWide = $($.parseHTML(data)).filterAllNodes('.js-add-product-wide-window').data('wide');
        const cssClass = isWide ? 'window-popup--wide' : 'window-popup--standard';

        if (buttonContinueUrl !== undefined) {
            // eslint-disable-next-line no-new
            new Window({
                content: data,
                buttonClose: false,
                cssClass: 'window-popup--wide window-popup--no-padding',
                textContinue: '',
                urlContinue: buttonContinueUrl
            });

            $('#js-cart-box').trigger('reload');

            // get cart item details
            let productName = $($.parseHTML(data)).filterAllNodes('[data-ecomail-name]').data('ecomail-name');
            let productPrice = $($.parseHTML(data)).filterAllNodes('[data-ecomail-price]').data('ecomail-price');
            let productUrl = $($.parseHTML(data)).filterAllNodes('[data-ecomail-url]').data('ecomail-url');
            let productId = $($.parseHTML(data)).filterAllNodes('[data-ecomail-id]').data('ecomail-id');
            let productImage = $($.parseHTML(data)).filterAllNodes('[data-ecomail-image]').data('ecomail-image');

            let productData = {
                productId: productId,
                img_url: productImage,
                url: productUrl,
                name: productName,
                price: productPrice
            };

            if (window.localStorage.getItem('ecomail-cart-products') === null) {
                var ecomailCartProducts = [];
            } else {
                var ecomailCartProducts = JSON.parse(window.localStorage.getItem('ecomail-cart-products'));
            }

            ecomailCartProducts.push(productData);
            window.localStorage.setItem('ecomail-cart-products', JSON.stringify(ecomailCartProducts));

            // send cart event to ecomail
            window.ecotrack('trackUnstructEvent', {
                schema: '',
                data: {
                    action: 'Basket',
                    products: ecomailCartProducts
                }
            });
        } else {
            // eslint-disable-next-line no-new
            new Window({
                content: data,
                cssClass: cssClass,
                buttonCancel: true,
                textCancel: Translator.trans('Close'),
                cssClassCancel: 'btn--success'
            });
        }
    }

    static onError (jqXHR) {
        // on FireFox abort ajax request, but request was probably successful
        if (jqXHR.status !== 0) {
            // eslint-disable-next-line no-new
            new Window({
                content: Translator.trans('Operation failed')
            });
        }
    }

    static init ($container) {
        $container.filterAllNodes('form.js-add-product').on('submit.addProductAjaxSubmit', AddProduct.ajaxSubmit);
    }
}

new Register().registerCallback(AddProduct.init);

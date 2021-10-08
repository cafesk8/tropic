import 'framework/common/components';
import Register from 'framework/common/utils/Register';
import Window from '../utils/Window';

export default class PreorderModal {

    static showModal (event) {
        if ($(this).data('hard-disabled')) {
            return false;
        }

        const productName = $(this).data('product-name');
		const productType = $(this).data('product-type');
		let productAvailability;

		if(productType == 'simple') {
        	productAvailability = $(this).closest('form').find('.in-availability__date').text();
		} else {
        	productAvailability = $(this).closest('.list-variants__item').find('.in-availability__date').text();
		}

        const $window = new Window({
            content: '<div class="js-window-content window-popup__in" style="overflow:hidden;">'
                + '<p>Vytvořte si závaznou předobjednávku a zarezervujte si produkt s dostupností ' + productAvailability + '.</p>'
                + '<p>Budeme Vás brzy kontaktovat ohledně dalšího postupu.</p>'
                + '<form id="js-preorder-form" method="POST" action="https://api2.ecomailapp.cz/lists/2/subscribe">'
                    + '<div class="form-line__input">'
                        + '<input type="text" id="preorder-name" class="input" required name="preorder-name" style="padding-bottom: 15px;" placeholder="Váše jméno">'
                    + '</div>'
					+ '<div class="form-line__input">'
                        + '<input type="email" id="preorder-email" class="input" required name="preorder-email" style="padding-bottom: 15px;" placeholder="Váš e-mail">'
                    + '</div>'
                    + '<div class="form-line__input">'
                        + '<input type="text" id="preorder-phone" class="input" required name="preorder-phone" style="padding-bottom: 15px;" placeholder="Váše tel. číslo">'
                    + '</div>'
                    + '<input type="hidden" name="preorder-product" id="preorder-product" value="' + productName + '">'
                    + '<input type="submit" class="btn--big btn" value="Odeslat">'
                + '</form>'
                + '<p class="js-preorder-thanks" style="display:none;">Děkujeme, brzy se Vám ozveme.</p>'
                + '</div>',
            cssClass: 'window-popup--wide'
        });
        $window.getWindow();

        event.preventDefault();
    }

    static sendToEcomail (event) {

        var request = new XMLHttpRequest();

        request.open('POST', 'https://api2.ecomailapp.cz/lists/12/subscribe');
        request.setRequestHeader('key', '5ca61068d0cf95ca61068d0da3');
        request.setRequestHeader('Content-Type', 'application/json');

        request.onreadystatechange = function ($this) {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    $('#js-preorder-form').remove();
                    $('.js-preorder-thanks').show();
                }
            }
        };

        var body = {
            'subscriber_data': {
                'email': $('#preorder-email').val(),
				'phone': $('#preorder-phone').val(),
				'name': $('#preorder-name').val(),
                'custom_fields': {
                    'product_name': $('#preorder-product').val()
                }
            }
        };
        request.send(JSON.stringify(body));
        event.preventDefault();
    }

    static init ($container) {
        $container.filterAllNodes('.js-preorder-modal').on('click.showPreorderModal', PreorderModal.showModal);
        $container.filterAllNodes('#js-preorder-form').on('submit', PreorderModal.sendToEcomail);
    }
}

new Register().registerCallback(PreorderModal.init);

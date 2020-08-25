import 'framework/common/components';
import Register from 'framework/common/utils/Register';
import Window from '../utils/Window';

export default class CofidisCalculator {

    static showCalculator (event) {
        if ($(this).data('hard-disabled')) {
            return false;
        }

        const productPrice = parseInt($(this).data('product-price'));

        const $window = new Window({
            content: '<iframe src="https://www.iplatba.cz/kalkulacka-nakupu-na-splatky/?cenaZbozi=' + productPrice + '&idObchodu=MjA5NDY=" class="cofidis-calculator"></iframe>',
            cssClass: 'window-popup--wide box-cofidis-calculator'
        });
        $window.getWindow();

        event.preventDefault();
    }

    static init ($container) {
        $container.filterAllNodes('.js-cofidis-calculator').on('click.showCofidisCalculator', CofidisCalculator.showCalculator);
    }
}

new Register().registerCallback(CofidisCalculator.init);

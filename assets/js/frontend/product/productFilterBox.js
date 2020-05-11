import Register from 'framework/common/utils/Register';

export default class ProductFilterBox {

    constructor ($container) {
        const _this = this;
        $container.filterAllNodes('.js-product-filter-box-button-show').on('click', event => {
            _this.showFilterBox($(event.target).closest('.js-product-filter-box'));
        });
        $container.filterAllNodes('.js-product-filter-box-button-hide').on('click', event => {
            _this.hideFilterBox($(event.target).closest('.js-product-filter-box'));
        });
    }

    showFilterBox ($parameterContainer) {
        $parameterContainer.find('.js-product-filter-box-label').each(function () {
            $(this).removeClass('inactive');
        });

        $parameterContainer.find('.js-product-filter-box-button-show').toggleClass('display-none');
        $parameterContainer.find('.js-product-filter-box-button-hide').toggleClass('display-none');
    }

    hideFilterBox ($parameterContainer) {
        $parameterContainer.find('.js-product-filter-box-label').each(function () {
            const $choiceInput = $(this).find('input');
            if ($choiceInput.prop('checked') === false) {
                $(this).addClass('inactive');
            }
        });

        $parameterContainer.find('.js-product-filter-box-button-show').toggleClass('display-none');
        $parameterContainer.find('.js-product-filter-box-button-hide').toggleClass('display-none');
    }

    static init ($container) {
        // eslint-disable-next-line no-new
        new ProductFilterBox($container);
    }
}

(new Register()).registerCallback(ProductFilterBox.init);

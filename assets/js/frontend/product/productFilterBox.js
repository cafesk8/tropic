import Register from 'framework/common/utils/Register';

export default class ProductFilterBox {

    constructor ($container) {
        const _this = this;
        $container.filterAllNodes('.js-product-filter-box-button').on('click', event => {
            _this.toggleFilterBox($(event.target).closest('.js-product-filter-box'));
        });
    }

    toggleFilterBox ($parameterContainer) {
        const $productFilterLabel = $parameterContainer.find('.js-product-filter-box-label');
        $productFilterLabel.toggleClass('inactive');

        const $productFilterButton = $parameterContainer.find('.js-product-filter-box-button');
        $productFilterButton.toggleClass('inactive');
    }

    static init ($container) {
        // eslint-disable-next-line no-new
        new ProductFilterBox($container);
    }
}

(new Register()).registerCallback(ProductFilterBox.init);

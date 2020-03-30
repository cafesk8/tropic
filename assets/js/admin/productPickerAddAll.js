import Ajax from 'framework/common/utils/Ajax';
import Register from 'framework/common/utils/Register';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.productPickerAll = Shopsys.productPickerAll || {};

    Shopsys.productPickerAll.ProductPickerAll = function ($wrapper) {

        const $addButton = $wrapper.filterAllNodes('.js-product-picker-add-all');
        const $removeButton = $wrapper.filterAllNodes('.js-product-picker-remove-all');

        this.init = function () {
            $addButton.on('click', onAddButtonClick);
            $removeButton.on('click', onRemoveButtonClick);
        };

        const onAddButtonClick = function () {
            processForm(function (productsPicker, productIdsWithNames) {
                $.each(productIdsWithNames, function (index, productIdWithName) {
                    if (productsPicker.hasProduct(productIdWithName.id) === false) {
                        productsPicker.addProduct(
                            productIdWithName.id,
                            productIdWithName.name
                        );
                    }
                });
            });
        };

        const onRemoveButtonClick = function () {
            processForm(function (productsPicker, productIdsWithNames) {
                $.each(productIdsWithNames, function (index, productIdWithName) {
                    if (productsPicker.hasProduct(productIdWithName.id) === true) {
                        productsPicker.removeItemByProductId(productIdWithName.id);
                    }
                });
            });
        };

        const processForm = function (callbackForProcessAllItems) {
            const $form = $addButton.closest('form');

            Ajax.ajax({
                url: '/admin/product-picker/pick-all/',
                type: 'GET',
                data: $form.serialize(),
                dataType: 'html',
                success: function (data) {
                    const parsedData = JSON.parse(data);
                    if (typeof (parsedData.errorMessage) !== 'undefined') {
                        $('.js-product-picker-all').html(parsedData.errorMessage);
                    } else if (typeof (parsedData.products) !== 'undefined') {
                        const instanceId = $wrapper.attr('data-js-instance-id');
                        const productsPicker = window.parent.ProductsPickerInstances[instanceId];

                        callbackForProcessAllItems(productsPicker, parsedData.products);

                        window.parent.$.magnificPopup.instance.close();
                    }
                }
            });
        };

    };

    new Register().registerCallback(function ($container) {
        $container.filterAllNodes('.js-product-picker-all').each(function () {
            const productPickerAll = new Shopsys.productPickerAll.ProductPickerAll($(this));
            productPickerAll.init();
        });
    });

})(jQuery);

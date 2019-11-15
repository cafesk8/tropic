(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.productPickerAll = Shopsys.productPickerAll || {};

    Shopsys.productPickerAll.ProductPickerAll = function ($wrapper) {

        var $addButton = $wrapper.filterAllNodes('.js-product-picker-add-all');
        var $removeButton = $wrapper.filterAllNodes('.js-product-picker-remove-all');

        this.init = function () {
            $addButton.on('click', onAddButtonClick);
            $removeButton.on('click', onRemoveButtonClick);
        };

        var onAddButtonClick = function () {
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

        var onRemoveButtonClick = function () {
            processForm(function (productsPicker, productIdsWithNames) {
                $.each(productIdsWithNames, function (index, productIdWithName) {
                    if (productsPicker.hasProduct(productIdWithName.id) === true) {
                        productsPicker.removeItemByProductId(productIdWithName.id);
                    }
                });
            });
        };

        var processForm = function (callbackForProcessAllItems) {
            var $form = $addButton.closest('form');

            Shopsys.ajax({
                url: '/admin/product-picker/pick-all/',
                type: 'GET',
                data: $form.serialize(),
                dataType: 'html',
                success: function (data) {
                    var productIdsWithNames = JSON.parse(data);

                    var instanceId = $wrapper.attr('data-js-instance-id');
                    var productsPicker = window.parent.Shopsys.productsPicker.instances[instanceId];

                    callbackForProcessAllItems(productsPicker, productIdsWithNames);

                    Shopsys.productsPicker.close();
                }
            });
        };

    };

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('.js-product-picker-all').each(function () {
            var productPickerAll = new Shopsys.productPickerAll.ProductPickerAll($(this));
            productPickerAll.init();
        });
    });

})(jQuery);

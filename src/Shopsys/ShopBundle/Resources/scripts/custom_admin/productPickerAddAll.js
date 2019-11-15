(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.productPickerAll = Shopsys.productPickerAll || {};

    Shopsys.productPickerAll.ProductPickerAll = function ($wrapper) {

        var $addButton = $wrapper.filterAllNodes('.js-product-picker-add-all');

        this.init = function () {
            $addButton.on('click', onAddButtonClick);
        };

        var onAddButtonClick = function () {
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

                    $.each(productIdsWithNames, function (index, productIdWithName) {
                        if (productsPicker.hasProduct(productIdWithName.id) === false) {
                            productsPicker.addProduct(
                                productIdWithName.id,
                                productIdWithName.name
                            );
                        }
                    });

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

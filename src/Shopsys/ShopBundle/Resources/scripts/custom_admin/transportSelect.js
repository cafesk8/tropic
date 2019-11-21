(function ($) {

    Shopsys = Shopsys || {};
    Shopsys.transportSelect = Shopsys.transportSelect || {};

    Shopsys.transportSelect.TransportSelect = function ($container) {
        var $shipperSelect = $container.filterAllNodes('.js-transport-select-shipper');
        var $shipperServiceSelect = $container.filterAllNodes('.js-transport-select-shipper-service');
        var $transportForm = $container.filterAllNodes('form[name=transport_form]');

        this.init = function () {
            this.reloadDependendInputs();
            $transportForm.find('.js-transport-type').on('change', this.reloadDependendInputs);

            $shipperSelect.change(function () {
                var $shipperSelector = $(this);
                var $shipperSelectorValue = $shipperSelector.val();
                var data = null;
                if ($shipperSelectorValue !== '') {
                    data = { 'shipper': $shipperSelector.val() };
                }

                Shopsys.ajax({
                    overlayDelay: 0,
                    method: 'GET',
                    loaderElement: $shipperServiceSelect,
                    url: $shipperSelector.data('url'),
                    data: data,
                    dataType: 'JSON',
                    success: function (data) {
                        $shipperServiceSelect.html('');
                        var $option = $($.parseHTML('<option/>'));

                        if (data.length > 0) {
                            $shipperServiceSelect.append($option.clone().val('').text(Shopsys.translator.trans('Vyberte službu dopravce')));
                        } else {
                            $shipperServiceSelect.append($option.clone().val('').text(Shopsys.translator.trans('Výchozí služba dopravce')));
                        }
                        $.each(data, function (key, data) {
                            $shipperServiceSelect.append($option.clone().val(data.id).text(data.name));
                        });

                    }
                });
            });
        };

        this.reloadDependendInputs = function () {
            if ($transportForm.find('.js-transport-type').val() === Shopsys.constant('\\Shopsys\\ShopBundle\\Model\\Transport\\Transport::TYPE_PERSONAL_TAKE_BALIKOBOT')) {
                $transportForm.filterAllNodes('.js-transport-depend-on-balikobot').closest('.form-line').removeClass('display-none');
            } else {
                $transportForm.filterAllNodes('.js-transport-depend-on-balikobot').closest('.form-line').addClass('display-none');
            }
        };
    };

    Shopsys.register.registerCallback(function ($container) {
        var transportSelect = new Shopsys.transportSelect.TransportSelect($container);
        transportSelect.init();
    });

})(jQuery);

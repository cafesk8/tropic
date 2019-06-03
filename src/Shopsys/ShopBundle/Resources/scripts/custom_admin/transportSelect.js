(function ($) {

    Shopsys = Shopsys || {};
    Shopsys.transportSelect = Shopsys.transportSelect || {};

    Shopsys.transportSelect.TransportSelect = function ($container) {
        var $shipperSelect = $container.filterAllNodes('.js-transport-select-shipper');
        var $shipperServiceSelect = $container.filterAllNodes('.js-transport-select-shipper-service');

        this.init = function () {
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

                        $shipperServiceSelect.append($option.clone().val('').text(Shopsys.translator.trans('Vyberte službu dopravce')));
                        $.each(data, function (key, data) {
                            $shipperServiceSelect.append($option.clone().val(data.id).text(data.name));
                        });

                    }
                });
            });
        };
    };

    Shopsys.register.registerCallback(function ($container) {
        var transportSelect = new Shopsys.transportSelect.TransportSelect($container);
        transportSelect.init();
    });

})(jQuery);

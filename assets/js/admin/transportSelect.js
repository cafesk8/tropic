import Ajax from 'framework/common/utils/ajax';
import Register from 'framework/common/utils/register';
import Translator from 'bazinga-translator';

(function ($) {

    const Shopsys = Shopsys || {};
    Shopsys.transportSelect = Shopsys.transportSelect || {};

    Shopsys.transportSelect.TransportSelect = function ($container) {
        const $shipperSelect = $container.filterAllNodes('.js-transport-select-shipper');
        const $shipperServiceSelect = $container.filterAllNodes('.js-transport-select-shipper-service');
        const $transportForm = $container.filterAllNodes('form[name=transport_form]');

        this.init = function () {
            this.reloadDependendInputs();
            $transportForm.find('.js-transport-type').on('change', this.reloadDependendInputs);

            $shipperSelect.change(function () {
                const $shipperSelector = $(this);
                const $shipperSelectorValue = $shipperSelector.val();
                let data = null;
                if ($shipperSelectorValue !== '') {
                    data = { 'shipper': $shipperSelector.val() };
                }

                Ajax.ajax({
                    overlayDelay: 0,
                    method: 'GET',
                    loaderElement: $shipperServiceSelect,
                    url: $shipperSelector.data('url'),
                    data: data,
                    dataType: 'JSON',
                    success: function (data) {
                        $shipperServiceSelect.html('');
                        const $option = $($.parseHTML('<option/>'));

                        if (data.length > 0) {
                            $shipperServiceSelect.append($option.clone().val('').text(Translator.trans('Vyberte službu dopravce')));
                        } else {
                            $shipperServiceSelect.append($option.clone().val('').text(Translator.trans('Výchozí služba dopravce')));
                        }
                        $.each(data, function (key, data) {
                            $shipperServiceSelect.append($option.clone().val(data.id).text(data.name));
                        });

                    }
                });
            });
        };

        this.reloadDependendInputs = function () {
            if ($transportForm.find('.js-transport-type').val() === 'balikobot') {
                $transportForm.filterAllNodes('.js-transport-depend-on-balikobot').closest('.form-line').removeClass('display-none');
                $transportForm.find('.js-transport-type').parent().siblings('.js-tooltip').show();
            } else {
                $transportForm.filterAllNodes('.js-transport-depend-on-balikobot').closest('.form-line').addClass('display-none');
                $transportForm.find('.js-transport-type').parent().siblings('.js-tooltip').hide();
            }
        };
    };

    new Register().registerCallback(function ($container) {
        const transportSelect = new Shopsys.transportSelect.TransportSelect($container);
        transportSelect.init();
    });

})(jQuery);

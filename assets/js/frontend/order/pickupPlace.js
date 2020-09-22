import Ajax from 'framework/common/utils/Ajax';
import Window from '../utils/Window';
import Register from 'framework/common/utils/Register';
import Timeout from 'framework/common/utils/Timeout';
import windowClose from '../utils/windowFunctions';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.pickupPlaceSelection = Shopsys.pickupPlaceSelection || {};

    const configurations = [
        {
            dataIsPickupPlaceAttribute: 'transport-pickup',
            pickupPlaceInput: 'js-pickup-place-input'
        },
        {
            dataIsPickupPlaceAttribute: 'choose-store',
            pickupPlaceInput: 'js-store-input'
        }
    ];

    Shopsys.pickupPlaceSelection.init = function ($container) {
        $container.filterAllNodes('.js-pickup-place-button').click(Shopsys.pickupPlaceSelection.onSelectPlaceButtonClick);
        $container.filterAllNodes('.js-pickup-place-change-button').click(Shopsys.pickupPlaceSelection.onChangeButtonClick);
        $container.filterAllNodes('.js-order-transport-input').change(Shopsys.pickupPlaceSelection.onTransportChange);
        $container.filterAllNodes('.js-pickup-place-city-post-code-autocomplete-input').bind('keyup paste', Shopsys.pickupPlaceSelection.onSearchAutocompleteInputChange);

        Shopsys.pickupPlaceSelection.initListPickUpPlaceSelectors($container);
    };

    Shopsys.pickupPlaceSelection.activatePickUpPlaceSelector = function ($pickupPlaceSelector) {
        $('.js-pickup-place-row').removeClass('active');
        $pickupPlaceSelector.addClass('active');
    };

    Shopsys.pickupPlaceSelection.initListPickUpPlaceSelectors = function ($container) {
        $container.filterAllNodes('.js-pickup-place').each(function () {
            $(this).click(function () {
                Shopsys.pickupPlaceSelection.activatePickUpPlaceSelector($(this));
            });
        });
    };

    Shopsys.pickupPlaceSelection.onTransportChange = function (event) {
        const $transportInput = $('#js-window').data('transportInput');
        const $selectedTransportInput = $(this);

        configurations.forEach(function (config) {
            const isPickUpPlaceTransportType = $selectedTransportInput.data(config.dataIsPickupPlaceAttribute);
            const transportType = $selectedTransportInput.data('type');

            if (isPickUpPlaceTransportType && $selectedTransportInput.prop('checked') && ($transportInput === undefined || $transportInput[0] !== $selectedTransportInput[0])) {
                if (transportType === 'zasilkovnaCZ' || transportType === 'zasilkovnaSK') {
                    Shopsys.pickupPlaceSelection.pickPacketa($selectedTransportInput);
                } else {
                    Shopsys.pickupPlaceSelection.showSearchWindow($selectedTransportInput, config.pickupPlaceInput);
                    $selectedTransportInput.prop('checked', false);
                }
            }
        });

        event.preventDefault();
    };

    Shopsys.pickupPlaceSelection.showSearchWindow = function ($selectedTransportInput, pickupPlaceInputClass) {
        const $pickupPlaceInput = $('.' + pickupPlaceInputClass);
        const pickupPlaceInput = $pickupPlaceInput.val();
        const pickupPlaceValue = (pickupPlaceInput !== '') ? pickupPlaceInput : null;

        Ajax.ajax({
            url: $pickupPlaceInput.data('search-url'),
            dataType: 'html',
            data: {
                pickupPlaceId: pickupPlaceValue,
                transportId: $selectedTransportInput.data('id')
            },
            success: function (data) {
                /* eslint-disable no-new */
                const $window = new Window({
                    content: data,
                    cssClass: 'window-popup--wide box-pickup-place'
                });
                $window.getWindow().data('transportInput', $selectedTransportInput);
            }
        });
    };

    Shopsys.pickupPlaceSelection.onSearchAutocompleteInputChange = function (event) {
        const $searchContainer = $(event.currentTarget).closest('.js-pickup-place-search');
        const $autocompleteResults = $searchContainer.find('.js-pickup-place-autocomplete-results');

        $('.js-pickup-place-autocomplete-results-detail').html('');

        Timeout.setTimeoutAndClearPrevious('Shopsys.pickupPlaceSelection.onSearchAutocompleteInputChange', function () {
            $autocompleteResults.show();
            Ajax.ajax({
                url: $searchContainer.data('pickup-place-autocomplete-url'),
                loaderElement: $autocompleteResults,
                dataType: 'html',
                method: 'post',
                data: {
                    searchQuery: $searchContainer.find('.js-pickup-place-city-post-code-autocomplete-input').val(),
                    transportId: $('#js-window').data('transportInput').data('id')
                },
                success: function (data) {
                    $autocompleteResults.html(data);
                    (new Register()).registerNewContent($autocompleteResults);

                    $('#js-window').resize();
                }
            });
        }, 200);
    };

    Shopsys.pickupPlaceSelection.onSelectPlaceButtonClick = function (event) {
        const $button = $(event.currentTarget);

        const $pickupPlaceInput = $('.' + $button.data('form-field-class'));
        $pickupPlaceInput.val($button.data('id'));

        const $transportInput = $('#js-window').data('transportInput');
        if ($transportInput.prop('disabled') !== true) {
            $transportInput.prop('checked', true).change();
        }

        $('#transport_and_payment_form_transport .js-pickup-place-detail').addClass('display-none');

        const $pickupPlaceDetail = $('#transport_and_payment_form_transport .js-pickup-place-detail-' + $transportInput.data('id'));

        $pickupPlaceDetail.addClass('display-none');
        $pickupPlaceDetail.removeClass('display-none')
            .attr('title', $button.data('description'))
            .tooltip('destroy');

        $pickupPlaceDetail.find('.js-pickup-place-detail-name')
            .text($button.data('name'));

        $pickupPlaceDetail.find('.js-pickup-place-change-button').toggle($button.data('name').length > 0);

        windowClose();
    };

    Shopsys.pickupPlaceSelection.onChangeButtonClick = function (event) {
        const $button = $(event.currentTarget);
        const $transportContainer = $button.closest('.js-order-transport');
        const $selectedTransportInput = $transportContainer.find('.js-order-transport-input[data-id=' + $button.data('id') + ']');
        const transportType = $button.data('type');

        if (transportType === 'zasilkovnaCZ' || transportType === 'zasilkovnaSK') {
            Shopsys.pickupPlaceSelection.pickPacketa($button);
        } else {
            Shopsys.pickupPlaceSelection.showSearchWindow($selectedTransportInput, $button.data('form-field-class'));
        }
    };

    Shopsys.pickupPlaceSelection.pickPacketa = function ($button) {
        const language = $button.data('language');
        let options = { language: language };

        if (language === 'cs') {
            options.country = 'cz';
        } else if (language === 'sk') {
            options.country = 'sk';
        }

        Packeta.Widget.pick('de4e7603bb838a8e', function (place) {
            if (place !== null) {
                $('#transport_and_payment_form_packetaId').val(place.id);
                $('#transport_and_payment_form_packetaName').val(place.name);
                $('#transport_and_payment_form_packetaStreet').val(place.street);
                $('#transport_and_payment_form_packetaCity').val(place.city);
                $('#transport_and_payment_form_packetaZip').val(place.zip);
                $('#transport_and_payment_form_packetaCountry').val(place.country);
                $button.parents('.js-order-transport').find('.js-pickup-place-detail-name').text(place.name);
            }

            $button.parents('.js-order-transport').find('.js-pickup-place-change-button').removeClass('display-none');
        }, options);
    };

    new Register().registerCallback(Shopsys.pickupPlaceSelection.init);
})(jQuery);

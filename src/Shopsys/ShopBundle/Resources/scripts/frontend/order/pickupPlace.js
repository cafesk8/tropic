(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.pickupPlaceSelection = Shopsys.pickupPlaceSelection || {};

    var configurations = [
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
        var $transportInput = $('#js-window').data('transportInput');
        var $selectedTransportInput = $(this);

        configurations.forEach(function (config) {
            var isPickUpPlaceTransportType = $selectedTransportInput.data(config.dataIsPickupPlaceAttribute);

            if (isPickUpPlaceTransportType && $selectedTransportInput.prop('checked') && ($transportInput === undefined || $transportInput[0] !== $selectedTransportInput[0])) {
                Shopsys.pickupPlaceSelection.showSearchWindow($selectedTransportInput, config.pickupPlaceInput);
                $selectedTransportInput.prop('checked', false);
            }
        });

        event.preventDefault();
    };

    Shopsys.pickupPlaceSelection.showSearchWindow = function ($selectedTransportInput, pickupPlaceInputClass) {
        var $pickupPlaceInput = $('.' + pickupPlaceInputClass);
        var pickupPlaceInput = $pickupPlaceInput.val();
        var pickupPlaceValue = (pickupPlaceInput !== '') ? pickupPlaceInput : null;

        Shopsys.ajax({
            url: $pickupPlaceInput.data('search-url'),
            dataType: 'html',
            data: {
                pickupPlaceId: pickupPlaceValue,
                transportId: $selectedTransportInput.data('id')
            },
            success: function (data) {
                var $window = Shopsys.window({
                    content: data,
                    cssClass: 'window-popup--standard box-pickup-place'
                });
                $window.data('transportInput', $selectedTransportInput);
            }
        });
    };

    Shopsys.pickupPlaceSelection.onSearchAutocompleteInputChange = function () {
        var $searchContainer = $(this).closest('.js-pickup-place-search');
        var $autocompleteResults = $searchContainer.find('.js-pickup-place-autocomplete-results');

        $('.js-pickup-place-autocomplete-results-detail').html('');

        Shopsys.timeout.setTimeoutAndClearPrevious('Shopsys.pickupPlaceSelection.onSearchAutocompleteInputChange', function () {
            $autocompleteResults.show();
            Shopsys.ajax({
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
                    Shopsys.register.registerNewContent($autocompleteResults);

                    $('#js-window').resize();
                }
            });
        }, 200);
    };

    Shopsys.pickupPlaceSelection.onSelectPlaceButtonClick = function () {
        var $button = $(this);

        var $pickupPlaceInput = $('.' + $button.data('form-field-class'));
        $pickupPlaceInput.val($button.data('id'));

        var $transportInput = $('#js-window').data('transportInput');
        if ($transportInput.prop('disabled') !== true) {
            $transportInput.prop('checked', true).change();
        }

        $('#transport_and_payment_form_transport .js-pickup-place-detail').addClass('display-none');

        var $pickupPlaceDetail = $('#transport_and_payment_form_transport .js-pickup-place-detail-' + $transportInput.data('id'));

        $pickupPlaceDetail.addClass('display-none');
        $pickupPlaceDetail.removeClass('display-none')
            .attr('title', $button.data('description'))
            .tooltip('destroy');

        $pickupPlaceDetail.find('.js-pickup-place-detail-name')
            .text($button.data('name'));

        $pickupPlaceDetail.find('.js-pickup-place-change-button').toggle($button.data('name').length > 0);

        Shopsys.windowFunctions.close();
    };

    Shopsys.pickupPlaceSelection.onChangeButtonClick = function () {
        var $button = $(this);
        var $transportContainer = $button.closest('.js-order-transport');
        var $selectedTransportInput = $transportContainer.find('.js-order-transport-input[data-id=' + $button.data('id') + ']');

        Shopsys.pickupPlaceSelection.showSearchWindow($selectedTransportInput, $button.data('form-field-class'));
    };

    Shopsys.register.registerCallback(Shopsys.pickupPlaceSelection.init);

})(jQuery);

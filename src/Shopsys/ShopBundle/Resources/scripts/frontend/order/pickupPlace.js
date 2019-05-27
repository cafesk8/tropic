(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.pickupPlaceSelection = Shopsys.pickupPlaceSelection || {};

    var $pickupPlaceInput = null;

    Shopsys.pickupPlaceSelection.init = function ($container) {
        $pickupPlaceInput = $('.js-pickup-place-input');

        $container.filterAllNodes('.js-order-transport-input').change(Shopsys.pickupPlaceSelection.onTransportChange);
        $container.filterAllNodes('.js-pickup-place-city-post-code-autocomplete-input')
            .bind('keyup paste', Shopsys.pickupPlaceSelection.onSearchAutocompleteInputChange);
        $container.filterAllNodes('.js-pickup-place-button').click(Shopsys.pickupPlaceSelection.onSelectPlaceButtonClick);
        $container.filterAllNodes('.js-pickup-place-change-button').click(Shopsys.pickupPlaceSelection.onChangeButtonClick);
    };

    Shopsys.pickupPlaceSelection.onTransportChange = function (event) {
        var $transportInput = $('#js-window').data('transportInput');
        var isPickUpPlaceTransportType = $(this).data('transport-pickup');
        if (isPickUpPlaceTransportType && $(this).prop('checked') && ($transportInput === undefined || $transportInput[0] !== $(this)[0])) {
            Shopsys.pickupPlaceSelection.showSearchWindow($(this));

            $(this).prop('checked', false);
            event.stopImmediatePropagation();
            event.preventDefault();
        }
    };

    Shopsys.pickupPlaceSelection.showSearchWindow = function ($selectedTransportInput) {
        var pickupPlaceInput = $('#transport_and_payment_form_pickupPlace').val();
        var pickUpPlaceValue = (pickupPlaceInput !== '') ? pickupPlaceInput : null;

        Shopsys.ajax({
            url: $pickupPlaceInput.data('pickup-place-search-url'),
            dataType: 'html',
            data: {
                pickupPlaceId: pickUpPlaceValue,
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
        $pickupPlaceInput.val($button.data('id'));

        var $transportInput = $('#js-window').data('transportInput');
        if ($transportInput.prop('disabled') !== true) {
            $transportInput.prop('checked', true).change();
        }

        var $pickUpPlaceDetail = $('#transport_and_payment_form_transport .js-pickup-place-detail');

        $('.js-pickup-place-detail').addClass('display-none');
        $pickUpPlaceDetail.removeClass('display-none')
            .attr('title', $button.data('description'))
            .tooltip('destroy');

        $pickUpPlaceDetail.find('.js-pickup-place-detail-name')
            .text($button.data('name'));

        $pickUpPlaceDetail.find('.js-pickup-place-change-button').toggle($button.data('name').length > 0);

        Shopsys.windowFunctions.close();
    };

    Shopsys.pickupPlaceSelection.onChangeButtonClick = function () {
        var $button = $(this);
        var $transportContainer = $button.closest('.js-order-transport');
        var $selectedTransportInput = $transportContainer.find('.js-order-transport-input');

        Shopsys.pickupPlaceSelection.showSearchWindow($selectedTransportInput);
    };

    Shopsys.register.registerCallback(Shopsys.pickupPlaceSelection.init);

})(jQuery);

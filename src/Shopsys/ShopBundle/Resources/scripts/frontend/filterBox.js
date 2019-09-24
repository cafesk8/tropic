(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.filterBox = Shopsys.filterBox || {};

    Shopsys.register.registerCallback(function ($container) {
        var $selectedFiltersBox = $('#js-selected-filters-box');
        var $filterUncheckButtons = $container.filterAllNodes('.js-selected-filters-box-uncheck');
        var $filterRangeUncheckLinks = $container.filterAllNodes('.js-filter-box-uncheck-range');

        if ($selectedFiltersBox.length > 0) {
            $filterUncheckButtons.each(function () {
                var $filterUncheckButton = $(this);
                var formId = $filterUncheckButton.data('filter-form-id');
                var $formInput = $('[data-filter-name-with-entity-id="' + formId + '"]');

                uncheckCheckboxAndRefreshContent($filterUncheckButton, $formInput);
            });
        }

        if ($filterRangeUncheckLinks.length > 0) {
            $filterRangeUncheckLinks.each(function () {
                uncheckCheckboxRangeAndRefreshContent($(this));
            });
        }

        function uncheckCheckboxAndRefreshContent ($filterUncheckButton, $formInput) {
            $filterUncheckButton.click(function () {

                $formInput.prop('checked', false);

                $formInput.change();
            });
        }

        function uncheckCheckboxRangeAndRefreshContent ($filterRangeUncheckButton) {
            $filterRangeUncheckButton.on('click', function () {
                $filterRangeUncheckButton.closest('.js-product-filter');
                var formId = $filterRangeUncheckButton.data('filter-form-id');
                var $rangeFilter = $filterRangeUncheckButton.closest('.js-product-filter').find('.js-range-slider');
                var $inputId = $rangeFilter.data(formId === 'product_filter_form_minimalPrice' ? 'minimum-input-id' : 'maximum-input-id');
                var $formInput = $('#' + $inputId);
                $formInput.val('');
                $formInput.change();
            });
        }
    });

})(jQuery);

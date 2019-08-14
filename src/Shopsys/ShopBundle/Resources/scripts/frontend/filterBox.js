(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.filterBox = Shopsys.filterBox || {};

    Shopsys.register.registerCallback(function ($container) {
        var $selectedFiltersBox = $('#js-selected-filters-box');
        var $filterUncheckButtons = $container.filterAllNodes('.js-selected-filters-box-uncheck');

        if ($selectedFiltersBox.length > 0) {
            $filterUncheckButtons.each(function () {
                var $filterUncheckButton = $(this);
                var formId = $filterUncheckButton.data('filter-form-id');
                var $formInput = $('#' + formId);

                uncheckCheckboxAndRefreshContent($filterUncheckButton, $formInput);
            });
        }

        function uncheckCheckboxAndRefreshContent ($filterUncheckButton, $formInput) {
            $filterUncheckButton.click(function () {
                $formInput.prop('checked', false);

                $formInput.change();
            });
        }
    });

})(jQuery);

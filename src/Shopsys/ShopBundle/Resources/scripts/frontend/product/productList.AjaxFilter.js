(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.productList = Shopsys.productList || {};
    Shopsys.productList.AjaxFilter = Shopsys.productList.AjaxFilter || {};

    Shopsys.productList.AjaxFilter = function () {
        var $productsWithControls = $('.js-product-list-ajax-filter-products-with-controls');
        var $productFilterForm = $('form[name="product_filter_form"]');
        var $showResultsButton = $('.js-product-filter-show-result-button');
        var $selectedFiltersBox = $('#js-selected-filters-box');
        var requestTimer = null;
        var requestDelay = 1000;

        this.init = function () {
            $productFilterForm.change(function () {
                clearTimeout(requestTimer);
                requestTimer = setTimeout(submitFormWithAjax, requestDelay);
                Shopsys.history.pushReloadState(Shopsys.url.getBaseUrl() + '?' + $productFilterForm.serialize());
            });

            $showResultsButton.click(function () {
                var $productList = $('.js-product-list');
                $('html, body').animate({ scrollTop: $productList.offset().top }, 'slow');
                return false;
            });

            updateFiltersDisabled();
        };

        var showProducts = function ($wrappedData) {
            var $productsHtml = $wrappedData.find('.js-product-list-ajax-filter-products-with-controls');
            $productsWithControls.html($productsHtml.html());
            $productsWithControls.show();
            Shopsys.register.registerNewContent($productsWithControls);
        };

        var updateFiltersCounts = function ($wrappedData) {
            var $existingCountElements = $('.js-product-filter-count');
            var $newCountElements = $wrappedData.find('.js-product-filter-count');

            $newCountElements.each(function () {
                var $newCountElement = $(this);

                var $existingCountElement = $existingCountElements
                    .filter('[data-form-id="' + $newCountElement.data('form-id') + '"]');

                $existingCountElement.html($newCountElement.html());
            });
        };

        var updateFiltersDisabled = function () {
            $('.js-product-filter-count').each(function () {
                var $countElement = $(this);

                var $label = $countElement.closest('label');
                var $formElement = $('#' + $countElement.data('form-id'));

                if (willFilterZeroProducts($countElement)) {
                    if (!$formElement.is(':checked')) {
                        $label.addClass('in-disable');
                        $formElement.prop('disabled', true);
                    }
                } else {
                    $label.removeClass('in-disable');
                    $formElement.prop('disabled', false);
                }
            });
        };

        var willFilterZeroProducts = function ($countElement) {
            return $countElement.html().indexOf('(0)') !== -1;
        };

        var updateSelectedFilters = function ($wrappedData) {
            var $newSelectedFiltersBox = $wrappedData.filterAllNodes('#js-selected-filters-box');
            $selectedFiltersBox.html($newSelectedFiltersBox.html());
            Shopsys.register.registerNewContent($selectedFiltersBox);
        };

        var submitFormWithAjax = function () {
            Shopsys.ajax({
                overlayDelay: 0,
                url: Shopsys.url.getBaseUrl(),
                data: $productFilterForm.serialize(),
                success: function (data) {
                    var $wrappedData = $($.parseHTML('<div>' + data + '</div>'));

                    showProducts($wrappedData);
                    updateFiltersCounts($wrappedData);
                    updateFiltersDisabled();
                    updateSelectedFilters($wrappedData);
                }
            });
        };

    };

})(jQuery);

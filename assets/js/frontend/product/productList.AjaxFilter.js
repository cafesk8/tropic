import Register from 'framework/common/utils/Register';
import Ajax from 'framework/common/utils/Ajax';
import getBaseUrl from '../utils/url';
import pushReloadState from '../components/history';

export default class ProductListAjaxFilter {

    constructor ($filter) {
        this.$productsWithControls = $filter.filterAllNodes('.js-product-list-ajax-filter-products-with-controls');
        this.$productFilterForm = $filter.filterAllNodes('form[name="product_filter_form"]');
        this.$showResultsButton = $filter.filterAllNodes('.js-product-filter-show-result-button');
        this.$selectedFiltersBox = $filter.filterAllNodes('#js-selected-filters-box');
        this.$resetFilterButton = $filter.filterAllNodes('.js-product-filter-reset-button');
        this.$categoryTitle = $filter.filterAllNodes('.js-category-title');
        this.requestTimer = null;
        this.requestDelay = 1000;

        const _this = this;
        this.$productFilterForm.on('change', () => {
            clearTimeout(_this.requestTimer);
            _this.requestTimer = setTimeout(() => _this.submitFormWithAjax(_this), _this.requestDelay);
        });

        this.$showResultsButton.on('click', () => {
            this.scrollToListTop();
            return false;
        });

        this.$resetFilterButton.on('click', (event) => {
            _this.$productFilterForm.find(':radio, :checkbox').prop('checked', false);
            _this.$productFilterForm.find('textarea, :text, select').val('');
            _this.$productFilterForm.find('.js-product-filter-call-change-after-reset').change();
            clearTimeout(_this.requestTimer);
            const resetUrl = $(event.target).attr('href');
            pushReloadState(resetUrl);
            _this.submitFormWithAjax(_this);
            return false;
        });

        this.updateFiltersDisabled();
        this.refreshBrandLinks();
    }

    showProducts ($wrappedData) {
        const $productsHtml = $wrappedData.find('.js-product-list-ajax-filter-products-with-controls');
        const currentUrl = $wrappedData.filterAllNodes('#js-product-list-ajax-filter-current-url').val();
        this.$productsWithControls.html($productsHtml.html());
        this.$productsWithControls.show();
        pushReloadState(currentUrl);
        (new Register()).registerNewContent(this.$productsWithControls);
        this.scrollToListTop();
    }

    updateFiltersCounts ($wrappedData) {
        const $existingCountElements = $('.js-product-filter-count');
        const $newCountElements = $wrappedData.find('.js-product-filter-count');

        $newCountElements.each((index, element) => {
            const $newCountElement = $(element);

            const $existingCountElement = $existingCountElements
                .filter('[data-form-id="' + $newCountElement.data('form-id') + '"]');

            $existingCountElement.html($newCountElement.html());
        });
    }

    updateBrandLabelTexts ($wrappedData) {
        const $existingBrandElements = $('.js-brand-label-text');
        const $newBrandElements = $wrappedData.find('.js-brand-label-text');

        $newBrandElements.each((index, element) => {
            const $newBrandElement = $(element);
            const $existingCountElement = $existingBrandElements
                .filter('[data-form-id="' + $newBrandElement.data('form-id') + '"]');

            $existingCountElement.html($newBrandElement.html());
        });
    }

    updateFiltersDisabled () {
        $('.js-product-filter-count').each(function (index, element) {
            const $countElement = $(element);

            const $label = $countElement.closest('label');
            const $formElement = $('#' + $countElement.data('form-id'));

            if (ProductListAjaxFilter.willFilterZeroProducts($countElement)) {
                if (!$formElement.is(':checked')) {
                    $label.addClass('in-disable');
                    $formElement.prop('disabled', true);
                }
            } else {
                $label.removeClass('in-disable');
                $formElement.prop('disabled', false);
            }
        });
    }

    updateTitle ($wrappedData) {
        const title = $wrappedData.filterAllNodes('#js-product-list-ajax-category-title').val();
        const $titleElement = $('title');

        this.$categoryTitle.html(title);
        let titleParts = $titleElement.text().split('|');
        titleParts[0] = title;
        titleParts[1] = titleParts[1].trim();
        $titleElement.text(titleParts.join(' | '));
    }

    submitFormWithAjax (productListAjaxFilter) {
        Ajax.ajax({
            overlayDelay: 0,
            url: getBaseUrl(),
            data: productListAjaxFilter.$productFilterForm.serialize(),
            success: function (data) {
                const $wrappedData = $($.parseHTML('<div>' + data + '</div>'));

                productListAjaxFilter.showProducts($wrappedData);
                productListAjaxFilter.updateFiltersCounts($wrappedData);
                productListAjaxFilter.updateFiltersDisabled();
                productListAjaxFilter.updateSelectedFilters($wrappedData);
                productListAjaxFilter.updateBrandLabelTexts($wrappedData);
                productListAjaxFilter.refreshBrandLinks();
                productListAjaxFilter.updateMetaTag($wrappedData);
                productListAjaxFilter.updateTitle($wrappedData);
            }
        });
    }

    static willFilterZeroProducts ($countElement) {
        return $countElement.html().indexOf('(0)') !== -1;
    }

    updateSelectedFilters ($wrappedData) {
        const $newSelectedFiltersBox = $wrappedData.filterAllNodes('#js-selected-filters-box');
        this.$selectedFiltersBox.html($newSelectedFiltersBox.html());
        (new Register()).registerNewContent(this.$selectedFiltersBox);
    };

    refreshBrandLinks () {
        $('.js-brand-filter-link').click((event) => {
            event.preventDefault();
            $('.form-choice__input[data-filter-name-with-entity-id="' + $(event.target).data('brand-checkbox-id') + '"]').click();
        });
    }

    updateMetaTag ($wrappedData) {
        const $indexingDisabled = $wrappedData.filterAllNodes('#js-disable-indexing');

        if ($indexingDisabled.val()) {
            if ($('#js-disable-indexing-ajax-meta').length === 0) {
                $('head').append('<meta id="js-disable-indexing-ajax-meta" name="robots" content="noindex, follow">');
            }
        } else {
            $('#js-disable-indexing-ajax-meta').remove();
        }
    }

    scrollToListTop () {
        const $productList = $('.js-product-list-ajax-filter-products-with-controls');
        if ($productList && $productList.offset()) {
            $('.js-product-filter-opener').click();
            $('html, body').animate({ scrollTop: $productList.offset().top }, 'slow');
        }
    }

    static init ($container) {
        if ($container.filterAllNodes('.js-product-list-with-paginator').length > 0) {
            // eslint-disable-next-line no-new
            new ProductListAjaxFilter($container);
        }
    }
}

(new Register()).registerCallback(ProductListAjaxFilter.init);

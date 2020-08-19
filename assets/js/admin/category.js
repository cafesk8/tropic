import Register from 'framework/common/utils/Register';

(function ($) {

    const Shopsys = window.Shopsys || {};
    Shopsys.category = Shopsys.category || {};

    Shopsys.category.Category = function ($categoryForm) {
        const $preListingCategorySetting = $categoryForm.filterAllNodes('[name="category_form[settings][preListingCategory]"]');
        const $leftBannerTextInputs = $categoryForm.filterAllNodes('.js-category-left-banner-texts');
        const $rightBannerTextInputs = $categoryForm.filterAllNodes('.js-category-right-banner-texts');
        const $filtersToggleCheckbox = $categoryForm.filterAllNodes('.js-filters-toggle-checkbox');

        this.init = function () {
            $preListingCategorySetting.change(toggleAdditionalBannerTextInputs);
            toggleAdditionalBannerTextInputs();
            $filtersToggleCheckbox.change(toggleFilters);
        };

        function toggleAdditionalBannerTextInputs () {
            const isPreListingCategorySettingAllowed = $preListingCategorySetting.filterAllNodes('#category_form_settings_preListingCategory_yes').is(':checked');

            $leftBannerTextInputs.closest('.form-line').toggleClass('display-none', !isPreListingCategorySettingAllowed);
            $rightBannerTextInputs.closest('.form-line').toggleClass('display-none', !isPreListingCategorySettingAllowed);
        }

        function toggleFilters () {
            $('.js-filter-checkbox').prop('checked', this.checked);
        }
    };

    new Register().registerCallback(function ($container) {
        const category = new Shopsys.category.Category($('form[name="category_form"]'));
        category.init();
    });

})(jQuery);

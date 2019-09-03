(function ($) {

    Shopsys = window.Shopsys || {};
    Shopsys.category = Shopsys.category || {};

    Shopsys.category.Category = function ($categoryForm) {
        var $preListingCategorySetting = $categoryForm.filterAllNodes('[name="category_form[settings][preListingCategory]"]');
        var $leftBannerTextInputs = $categoryForm.filterAllNodes('.js-category-left-banner-texts');
        var $rightBannerTextInputs = $categoryForm.filterAllNodes('.js-category-right-banner-texts');

        this.init = function () {
            $preListingCategorySetting.change(toggleAdditionalBannerTextInputs);
            toggleAdditionalBannerTextInputs();
        };

        function toggleAdditionalBannerTextInputs () {
            var isPreListingCategorySettingAllowed = $preListingCategorySetting.filterAllNodes('#category_form_settings_preListingCategory_yes').is(':checked');

            $leftBannerTextInputs.closest('.form-line').toggleClass('display-none', !isPreListingCategorySettingAllowed);
            $rightBannerTextInputs.closest('.form-line').toggleClass('display-none', !isPreListingCategorySettingAllowed);
        }
    };

    Shopsys.register.registerCallback(function ($container) {
        var category = new Shopsys.category.Category($('form[name="category_form"]'));
        category.init();
    });

})(jQuery);

import Register from 'framework/common/utils/Register';

(function ($) {
    const Shopsys = window.Shopsys || {};
    Shopsys.advert = Shopsys.advert || {};

    Shopsys.advert.Advert = function ($advertForm) {
        var $positionSelector = $advertForm.filterAllNodes('#advert_form_settings_positionName');
        var $categoryTree = $advertForm.filterAllNodes('.js-category-tree-form').closest('.form-line');
        var $imageGroup = $advertForm.filterAllNodes('.js-advert-type-content');
        var $imageSizeRecommendationSelector = '.js-image-size-recommendation';
        var $imageSizeRecommendations = $imageGroup.filterAllNodes($imageSizeRecommendationSelector);

        this.init = function () {
            onPositionSelectorChange();

            $positionSelector.change(onPositionSelectorChange);
        };

        var onPositionSelectorChange = function () {
            $positionSelector.val() === 'category' ? $categoryTree.show() : $categoryTree.hide();
            $imageSizeRecommendations.hide();
            $imageGroup.filterAllNodes($imageSizeRecommendationSelector + '-' + $positionSelector.val()).show();
        };
    };

    new Register().registerCallback(function ($container) {
        var advert = new Shopsys.advert.Advert($('form[name="advert_form"]'));
        advert.init();
    });
})(jQuery);

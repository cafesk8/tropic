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
        let $mobileImageInput = $imageGroup.filterAllNodes('.js-mobile-image-input').closest('.form-line');
        let $mobilePreview = $imageGroup.filterAllNodes('.image-noticer-mobile-original, .image-noimage-mobile-original').closest('.form-line');
        let $mobileSizeRecommendation = $imageGroup.filterAllNodes('.js-mobile-image-size-recommendation');

        this.init = function () {
            onPositionSelectorChange();

            $positionSelector.change(onPositionSelectorChange);
        };

        var onPositionSelectorChange = function () {
            $positionSelector.val() === 'category' ? $categoryTree.show() : $categoryTree.hide();
            $imageSizeRecommendations.hide();
            $imageGroup.filterAllNodes($imageSizeRecommendationSelector + '-' + $positionSelector.val()).show();

            if ($positionSelector.val() === 'fourthRectangle') {
                $mobileImageInput.show();
                $mobilePreview.show();
                $mobileSizeRecommendation.show();
            } else {
                $mobileImageInput.hide();
                $mobilePreview.hide();
                $mobileSizeRecommendation.hide();
            }
        };
    };

    new Register().registerCallback(function ($container) {
        var advert = new Shopsys.advert.Advert($('form[name="advert_form"]'));
        advert.init();
    });
})(jQuery);

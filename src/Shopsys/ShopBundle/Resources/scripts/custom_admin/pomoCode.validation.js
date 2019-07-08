(function ($) {
    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('form[name="promo_code_form"]').jsFormValidator({
            'groups': function () {
                var groups = [Shopsys.constant('\\Shopsys\\FrameworkBundle\\Form\\ValidationGroup::VALIDATION_GROUP_DEFAULT')];

                if ($('#promo_code_form_restrictionGroup_unlimited').is(':checked') === false) {
                    groups.push(Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Admin\\PromoCodeFormTypeExtension::VALIDATION_GROUP_TYPE_NOT_UNLIMITED'));
                }

                return groups;
            }
        });

    });
})(jQuery);

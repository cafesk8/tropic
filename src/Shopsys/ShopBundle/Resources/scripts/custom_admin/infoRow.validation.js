(function ($) {

    Shopsys.register.registerCallback(function ($container) {
        $container.filterAllNodes('#js-info-row-form').jsFormValidator({
            'groups': function () {
                var groups = [Shopsys.constant('\\Shopsys\\FrameworkBundle\\Form\\ValidationGroup::VALIDATION_GROUP_DEFAULT')];

                if ($('#info_row_form_visibility_yes').is(':checked')) {
                    groups.push(Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Admin\\InfoRowFormType::VALIDATION_GROUP_IS_VISIBLE'));
                }

                return groups;
            }
        });
    });

})(jQuery);

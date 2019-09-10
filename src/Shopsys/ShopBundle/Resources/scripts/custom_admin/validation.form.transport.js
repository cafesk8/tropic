(function ($) {
    Shopsys.register.registerCallback(function ($container) {
        var $transportForm = $container.filterAllNodes('form[name=transport_form]');
        $transportForm.jsFormValidator({
            'groups': function () {
                var groups = [Shopsys.constant('\\Shopsys\\FrameworkBundle\\Form\\ValidationGroup::VALIDATION_GROUP_DEFAULT')];

                if ($transportForm.find('.js-transport-personal-take').val() === Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Admin\\TransportFormTypeExtension::PERSONAL_TAKE_TYPE_BALIKOBOT')) {
                    groups.push(Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Admin\\TransportFormTypeExtension::VALIDATION_GROUP_BALIKOBOT'));
                }

                return groups;
            }
        });
    });
})(jQuery);

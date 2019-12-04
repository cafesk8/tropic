(function ($) {
    Shopsys.register.registerCallback(function ($container) {
        var $transportForm = $container.filterAllNodes('form[name=transport_form]');
        $transportForm.jsFormValidator({
            'groups': function () {
                var groups = [Shopsys.constant('\\Shopsys\\FrameworkBundle\\Form\\ValidationGroup::VALIDATION_GROUP_DEFAULT')];

                if ($transportForm.find('.js-transport-type').val() === Shopsys.constant('\\Shopsys\\ShopBundle\\Model\\Transport\\Transport::TYPE_PERSONAL_TAKE_BALIKOBOT')) {
                    groups.push(Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Admin\\TransportFormTypeExtension::VALIDATION_GROUP_BALIKOBOT'));
                }

                return groups;
            }
        });
    });
})(jQuery);

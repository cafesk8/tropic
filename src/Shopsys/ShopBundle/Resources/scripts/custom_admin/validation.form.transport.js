(function ($) {
    $(document).ready(function () {
        var $transportForm = $('form[name=transport_form]');
        $transportForm.jsFormValidator({
            'groups': function () {
                var groups = [Shopsys.constant('\\Shopsys\\FrameworkBundle\\Form\\ValidationGroup::VALIDATION_GROUP_DEFAULT')];

                if ($transportForm.find('#transport_form_balikobotGroup_balikobot_yes').is(':checked')) {
                    groups.push(Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Admin\\TransportFormTypeExtension::VALIDATION_GROUP_BALIKOBOT'));
                }

                return groups;
            }
        });
    });
})(jQuery);

import constant from '../../utils/constant';

(function ($) {
    $(document).ready(function () {
        var $registrationForm = $('form[name="registration_form"]');
        $registrationForm.jsFormValidator({
            'groups': function () {
                var groups = [constant('\\Shopsys\\FrameworkBundle\\Form\\ValidationGroup::VALIDATION_GROUP_DEFAULT')];
                if ($registrationForm.find('#registration_form_userData_memberOfLoyaltyProgram').is(':checked')) {
                    groups.push('loyaltyProgramMember');
                }

                return groups;
            }
        });
    });
})(jQuery);

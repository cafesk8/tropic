(function ($) {
    $(document).ready(function () {
        var $registrationForm = $('form[name="registration_form"]');
        $registrationForm.jsFormValidator({
            'groups': function () {
                var groups = [Shopsys.constant('\\Shopsys\\FrameworkBundle\\Form\\ValidationGroup::VALIDATION_GROUP_DEFAULT')];
                if ($registrationForm.find('#registration_form_userData_memberOfBushmanClub').is(':checked')) {
                    groups.push(Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Front\\Registration\\RegistrationFormType::VALIDATION_GROUP_BUSHMAN_CLUB_MEMBER'));
                }

                return groups;
            }
        });
    });
})(jQuery);

import constant from '../../utils/constant';

(function ($) {
    $(document).ready(function () {
        var $registrationForm = $('form[name="registration_form"]');
        $registrationForm.jsFormValidator({
            'groups': function () {
                return [constant('\\Shopsys\\FrameworkBundle\\Form\\ValidationGroup::VALIDATION_GROUP_DEFAULT')];
            }
        });
    });
})(jQuery);

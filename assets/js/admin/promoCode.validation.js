import Register from 'framework/common/utils/Register';
import constant from '../frontend/utils/constant';

(function ($) {
    new Register().registerCallback(function ($container) {
        $container.filterAllNodes('form[name="promo_code_form"]').jsFormValidator({
            'groups': function () {
                var groups = [constant('\\Shopsys\\FrameworkBundle\\Form\\ValidationGroup::VALIDATION_GROUP_DEFAULT')];

                if ($('#promo_code_form_restrictionGroup_unlimited').is(':checked') === false) {
                    groups.push('NOT_UNLIMITED');
                }

                return groups;
            }
        });

    });
})(jQuery);

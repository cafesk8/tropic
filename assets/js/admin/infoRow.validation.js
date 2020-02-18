import Register from 'framework/common/utils/register';
import constant from 'framework/admin/utils/constant';

(function ($) {

    new Register().registerCallback(function ($container) {
        $container.filterAllNodes('#js-info-row-form').jsFormValidator({
            'groups': function () {
                const groups = [constant('\\Shopsys\\FrameworkBundle\\Form\\ValidationGroup::VALIDATION_GROUP_DEFAULT')];

                if ($('#info_row_form_visibility_yes').is(':checked')) {
                    groups.push('isVisible');
                }

                return groups;
            }
        });
    });

})(jQuery);

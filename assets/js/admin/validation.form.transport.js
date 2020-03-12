import constant from 'framework/admin/utils/constant';
import Register from 'framework/common/utils/Register';

(function ($) {
    new Register().registerCallback(function ($container) {
        const $transportForm = $container.filterAllNodes('form[name=transport_form]');
        $transportForm.jsFormValidator({
            'groups': function () {
                const groups = [constant('\\Shopsys\\FrameworkBundle\\Form\\ValidationGroup::VALIDATION_GROUP_DEFAULT')];

                if ($transportForm.find('.js-transport-type').val() === 'balikobot') {
                    groups.push('balikobot');
                }

                return groups;
            }
        });
    });
})(jQuery);

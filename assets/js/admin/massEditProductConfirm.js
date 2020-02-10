import Register from 'framework/common/utils/register';
import Translator from 'bazinga-translator';
import Window from 'framework/admin/utils/window';

(function ($) {

    /* eslint-disable no-use-before-define */
    const Shopsys = Shopsys || {};
    Shopsys.massEditConfirm = Shopsys.massEditConfirm || {};

    let isConfirmed = false;

    Shopsys.massEditConfirm.init = function ($container) {
        $container.filterAllNodes('.js-mass-edit-submit').click(function () {
            const $button = $(this);
            if (!isConfirmed) {
                const selectType = $('.js-mass-edit-select-type').val();
                let count;
                switch (selectType) {
                    case 'selectTypeChecked':
                        count = $('.js-grid-mass-action-select-row:checked').length;
                        break;
                    case 'selectTypeAllResults':
                        count = $('.js-grid').data('total-count');
                        break;
                }

                const subject = $('.js-mass-edit-subject option:selected').text().toLowerCase();
                const operation = $('.js-mass-edit-operation option:selected').text().toLowerCase();
                /* eslint-disable no-new */
                new Window({
                    content: Translator.trans(
                        'Opravdu chcete %operation% (%subject%) u %count% produktů?',
                        { '%operation%': operation, '%subject%': subject, '%count%': count }
                    ),
                    buttonCancel: true,
                    buttonContinue: true,
                    eventContinue: function () {
                        isConfirmed = true;
                        $button.trigger('click');
                    }
                });

                return false;
            }
        });

    };

    new Register().registerCallback(Shopsys.massEditConfirm.init);

})(jQuery);

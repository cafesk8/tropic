import Ajax from 'framework/common/utils/ajax';
import Register from 'framework/common/utils/register';

(function ($) {

    const Shopsys = Shopsys || {};
    Shopsys.massEdit = Shopsys.massEdit || {};

    Shopsys.massEdit.init = function ($container) {

        $container.filterAllNodes('#js-mass-edit-button').click(function () {
            $('#js-mass-edit').toggleClass('active');
        });

        const $form = $container.find('.js-mass-edit-form-wrapper form');
        const $partialWrapper = $form.find('.js-mass-edit-form-partial');

        const url = $partialWrapper.data('ajax-url');

        const replaceFormByAjax = function (data) {
            Ajax.ajax({
                loaderElement: '.js-mass-edit-form-wrapper form',
                url: url,
                method: 'POST',
                data: data,
                success: function (responseData) {
                    $partialWrapper.html(responseData);
                    Shopsys.register.registerNewContent($partialWrapper);
                    hideValueField();
                }
            });
        };

        $form.on('change', '.js-mass-edit-subject', function () {
            replaceFormByAjax({
                selectedSubjectName: $partialWrapper.find('.js-mass-edit-subject option:selected').attr('value')
            });
        });

        $form.on('change', '.js-mass-edit-operation', function () {
            replaceFormByAjax({
                selectedSubjectName: $partialWrapper.find('.js-mass-edit-subject option:selected').attr('value'),
                selectedOperationName: $partialWrapper.find('.js-mass-edit-operation option:selected').attr('value')
            });
        });

        const hideValueField = function () {
            const subjectValue = $('.js-mass-edit-subject').val();
            const operationValue = $('.js-mass-edit-operation').val();

            $('.js-hide-when-operation-is-remove').toggleClass('display-none', subjectValue === 'gifts' && operationValue === 'remove');
        };
    };

    new Register().registerCallback(Shopsys.massEdit.init);

})(jQuery);

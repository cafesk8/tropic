import CustomizeBundle from 'framework/common/validation/customizeBundle';
import Window from './Window';
import Translator from 'bazinga-translator';

export function findOrCreateErrorList ($formInput, elementName) {
    const errorListClass = CustomizeBundle.getErrorListClass(elementName);
    let $errorList = $('.' + errorListClass);
    if ($errorList.length === 0) {
        $errorList = $($.parseHTML(
            '<span class="form-error js-validation-errors-list ' + errorListClass + '">'
            + '<ul class="form-error__list"></ul>'
        + '</span>'
        ));
        $formInput.closest('.js-form-line').find('.form-line__error').append($errorList);
    }

    return $errorList;
}

export function showFormErrorsWindow (container) {
    const $formattedFormErrors = CustomizeBundle.getFormattedFormErrors(container);
    const $window = $('#js-window');

    const $errorListHtml = '<div class="text-left">'
        + Translator.trans('Prosím, zkontrolujte zadané hodnoty')
        + $formattedFormErrors[0].outerHTML
        + '</div>';

    const removeSubmitProtection = function () {
        $('.js-order-submit-button').removeAttr('additional-submit-protection');
    };

    if ($window.length === 0) {
        // eslint-disable-next-line no-new
        new Window({
            content: $errorListHtml,
            eventClose: removeSubmitProtection
        });
    } else {
        $window.filterAllNodes('.js-window-validation-errors')
            .html($errorListHtml)
            .removeClass('display-none');
        $window.eventClose = removeSubmitProtection;

    }

}

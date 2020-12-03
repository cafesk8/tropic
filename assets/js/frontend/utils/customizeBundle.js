import CustomizeBundle from 'framework/common/validation/customizeBundle';
import Window from './Window';
import Translator from 'bazinga-translator';
import {
    findElementsToHighlight,
    highlightSubmitButtons
} from '@shopsys/framework/js/common/validation/validationHelpers';

export function showErrors (errors, sourceId) {
    const $errorList = CustomizeBundle.findOrCreateErrorList($(this), sourceId);
    const $errorListUl = $errorList.find('ul:first');
    const $elementsToHighlight = findElementsToHighlight($(this));

    const errorSourceClass = 'js-error-source-id-' + sourceId;
    $errorListUl.find('li.' + errorSourceClass).remove();

    $.each(errors, function (key, message) {
        if (sourceId === 'form-error-order-personal-info-form-deliveryPostcode' || sourceId === 'form-error-order-personal-info-form-postcode') {
            $errorListUl.append(
                $('<li/>')
                    .addClass('js-validation-errors-message')
                    .addClass(errorSourceClass)
                    .html(message)
            );
        } else {
            $errorListUl.append(
                $('<li/>')
                    .addClass('js-validation-errors-message')
                    .addClass(errorSourceClass)
                    .text(message)
            );
        }
    });

    const hasErrors = $errorListUl.find('li').length > 0;
    $elementsToHighlight.toggleClass('form-input-error', hasErrors);
    $errorList.toggle(hasErrors);

    highlightSubmitButtons($(this).closest('form'));
}

export function findOrCreateErrorList ($formInput, elementName) {
    const errorListClass = CustomizeBundle.getErrorListClass(elementName);
    let $errorList = $('.' + errorListClass);
    if ($errorList.length === 0) {
        $errorList = $($.parseHTML(
            '<span class="form-error js-validation-errors-list ' + errorListClass + '">'
            + '<ul class="form-error__list"></ul>'
        + '</span>'
        ));
        $errorList.insertBefore($formInput);
        $formInput.closest('.js-form-line').find('.js-form-line-error').append($errorList);
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

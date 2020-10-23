import Register from 'framework/common/utils/Register';
import CustomizeBundle from '@shopsys/framework/js/common/validation/customizeBundle';
import Timeout from '@shopsys/framework/js/common/utils/Timeout';

export const initClasses = ($container) => {
    $container.filterAllNodes('.js-no-validate-button').click(function () {
        $(this).closest('form').addClass('js-no-validate');
    });

    $container.filterAllNodes('.js-validation-error-close').click(function () {
        $(this).closest('.js-validation-error').hide();
    });

    $container.filterAllNodes('.js-validation-error-toggle').click(function () {
        $(this)
            .closest('.js-validation-errors-list')
            .find('.js-validation-error')
            .toggle();
    });
};

export const highlightSubmitButtons = ($form) => {
    const $submitButtons = $form.find('.btn[type="submit"]:not(.js-no-validate-button)');

    if (isFormValid($form)) {
        $submitButtons.removeClass('btn--disabled');
    } else {
        $submitButtons.addClass('btn--disabled');
    }
};

export const isFormValid = (form) => {
    return $(form).find('.js-validation-errors-message:visible').length === 0;
};

export const elementBind = (element) => {
    if (!element.domNode) {
        return;
    }

    const $domNode = $(element.domNode);

    if ($domNode.closest('.js-no-validate').length > 0) {
        return;
    }

    let isJsFileUpload = false;
    if ($domNode.hasClass('js-validation-no-file-upload') === false) {
        isJsFileUpload = $domNode.closest('.js-file-upload').length > 0;
    }

    $domNode
        .bind('blur change', function (event) {
            if (this.jsFormValidator.id !== event.target.id) {
                return;
            }

            if (this.jsFormValidator) {
                event.preventDefault();

                if (isJsFileUpload !== true) {
                    validateWithParentsDelayed(this.jsFormValidator);

                }
            }
        })
        .focus(function () {
            $(this).closest('.form-input-error').removeClass('form-input-error');
        })
        .jsFormValidator({
            'showErrors': CustomizeBundle.showErrors
        });
};

export const validateWithParentsDelayed = (jsFormValidator) => {
    const delayedValidators = {};
    do {
        delayedValidators[jsFormValidator.id] = jsFormValidator;
        jsFormValidator = jsFormValidator.parent;
    } while (jsFormValidator);

    Timeout.setTimeoutAndClearPrevious('Shopsys.validation.validateWithParentsDelayed', () => CustomizeBundle.executeDelayedValidators(delayedValidators), 100);
    setTimeout(function () {
        $.each(delayedValidators, function () {
            let $element = $(this.domNode);

            if ($element.is('input')) {
                if (!$element.hasClass('form-input-error') && !$element.hasClass('foxentry-input-invalid')) {
                    $element.closest('.js-form-line').find('.js-validation-errors-list').hide();
                }

                highlightSubmitButtons($element.closest('form'));
            }
        });

    }, 300);
};

(new Register()).registerCallback(initClasses);

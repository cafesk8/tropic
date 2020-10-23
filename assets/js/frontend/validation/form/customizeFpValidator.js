import CustomizeBundle from '@shopsys/framework/js/common/validation/customizeBundle';
import DoubleFormSubmitProtection from '@shopsys/framework/js/common/utils/DoubleFormSubmitProtection';
import { elementBind, isFormValid } from '../validationInit';

FpJsFormValidator.customizeMethods._submitForm = FpJsFormValidator.customizeMethods.submitForm;

FpJsFormValidator.customizeMethods.submitForm = function (event) {
    if ($(':focus').hasClass('js-no-validate-button')) {
        return;
    }

    $('.js-window-validation-errors').addClass('display-none');
    const $form = $(this);

    if ($form.hasClass('js-no-validate')) {
        return;
    }

    const doubleFormSubmitProtection = new DoubleFormSubmitProtection();
    doubleFormSubmitProtection.protection(event);

    FpJsFormValidator.each(this, function (item) {
        const element = item.jsFormValidator;
        element.validateRecursively();
        element.onValidate.apply(element.domNode, [FpJsFormValidator.getAllErrors(element, {}), event]);
    });

    if (!FpJsFormValidator.ajax.queue) {
        if (!isFormValid(this)) {
            event.preventDefault();
            CustomizeBundle.showFormErrorsWindow(this);
        } else if (isFormValid($form) === true && $form.data('on-submit') !== undefined) {
            $(this).trigger($(this).data('on-submit'));
            event.preventDefault();
        }
    } else {
        event.preventDefault();

        FpJsFormValidator.ajax.callbacks.push(function () {
            FpJsFormValidator.ajax.callbacks = [];

            if (!isFormValid($form)) {
                CustomizeBundle.showFormErrorsWindow($form[0]);
            } else if ($form.data('on-submit') !== undefined) {
                $form.trigger($form.data('on-submit'));
            } else {
                $form.addClass('js-no-validate');
                $form.unbind('submit').submit();
            }
        });
    }
};

FpJsFormValidator.attachElement = function (element) {
    FpJsFormValidator._attachElement(element);
    elementBind(element);
};

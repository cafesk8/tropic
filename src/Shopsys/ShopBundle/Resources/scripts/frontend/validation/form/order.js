(function ($) {
    $(document).ready(function () {

        var $transportAndPaymentForm = $('#transport_and_payment_form');
        $transportAndPaymentForm.jsFormValidator({
            callbacks: {
                validateTransportPaymentRelation: function () {
                    // JS validation is not necessary as it is not possible to select
                    // an invalid combination of transport and payment.
                },
                validatePickupPlaceTransport: function () {
                    // JS validation is not necessary
                }
            }
        });

        var $orderPersonalInfoForm = $('form[name="order_personal_info_form"]');
        $orderPersonalInfoForm.jsFormValidator({
            'groups': function () {

                var groups = [Shopsys.constant('\\Shopsys\\FrameworkBundle\\Form\\ValidationGroup::VALIDATION_GROUP_DEFAULT')];
                if ($orderPersonalInfoForm.find('#order_personal_info_form_billingAddressFilled').is(':checked')) {
                    groups.push(Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Front\\Customer\\DeliveryAddressFormType::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS'));
                }
                if ($orderPersonalInfoForm.find('#order_personal_info_form_companyCustomer').is(':checked')) {
                    groups.push(Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Front\\Customer\\BillingAddressFormType::VALIDATION_GROUP_COMPANY_CUSTOMER'));
                }
                if ($orderPersonalInfoForm.find('#order_personal_info_form_deliveryStreet') !== null) {
                    groups.push(Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Front\\Order\\PersonalInfoFormType::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED'));
                }

                return groups;
            }
        });

        $('.js-personal-info-telephone').jsFormValidator({
            callbacks: {
                validateTelephone: function () {
                    validateTelephone($('.js-personal-info-telephone'));
                }
            }
        });

        $('#order_personal_info_form_deliveryTelephone').jsFormValidator({
            callbacks: {
                validateTelephone: function () {
                    validateTelephone($('#order_personal_info_form_deliveryTelephone'));
                }
            }
        });

        var validateTelephone = function ($phoneElement) {
            var errorListSelectorPrefix = '.js-validation-error-list-';
            var elementId = $phoneElement.attr('id');
            Shopsys.ajax({
                loaderElement: '#' + elementId,
                type: 'GET',
                url: $phoneElement.attr('data-validation-url'),
                data: 'telephone=' + $phoneElement.val(),
                success: function (data) {
                    var errorListSelector = errorListSelectorPrefix + elementId;
                    $(errorListSelector).hide();
                    $(errorListSelector + ' ul').empty();

                    if (data.isValid === false) {
                        $(errorListSelector + ' ul').append(
                            '<li class="js-validation-errors-message js-error-source-id-form-error-' + elementId + '">'
                             + Shopsys.translator.trans('Telefonní číslo musí začínat znakem +')
                             + '</li>'
                        );
                        $(errorListSelector).show();
                    }
                }
            });
        };

    });
})(jQuery);

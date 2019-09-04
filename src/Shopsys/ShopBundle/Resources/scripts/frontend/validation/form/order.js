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
                    groups.push(Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Front\\Order\\PersonalInfoFormType::VALIDATION_GROUP_BILLING_ADDRESS_FILLED'));
                }
                if ($orderPersonalInfoForm.find('#order_personal_info_form_companyCustomer').is(':checked')) {
                    groups.push(Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Front\\Customer\\BillingAddressFormType::VALIDATION_GROUP_COMPANY_CUSTOMER'));
                }
                if ($orderPersonalInfoForm.find('#order_personal_info_form_deliveryStreet') !== null) {
                    groups.push(Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Front\\Order\\PersonalInfoFormType::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED'));
                }
                if (isGermanDomain) {
                    groups.push(Shopsys.constant('\\Shopsys\\ShopBundle\\Form\\Front\\Order\\PersonalInfoFormType::VALIDATION_GROUP_PHONE_PLUS_REQUIRED'));
                }

                return groups;
            }
        });
    });
})(jQuery);

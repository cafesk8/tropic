<?php

declare(strict_types=1);

namespace App\Model\Mail;

use App\Model\Order\Mail\OrderMail;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplateConfiguration as BaseMailTemplateConfiguration;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplateVariables;

/**
 * @property \App\Model\Order\Status\OrderStatusFacade $orderStatusFacade
 * @method __construct(\App\Model\Order\Status\OrderStatusFacade $orderStatusFacade)
 */
class MailTemplateConfiguration extends BaseMailTemplateConfiguration
{
    /**
     * copy-pasted from parent and added custom variables,
     * @see https://github.com/shopsys/shopsys/issues/1847
     */
    public function registerOrderStatusMailTemplates(): void
    {
        $orderStatusMailTemplate = new MailTemplateVariables('', self::TYPE_ORDER_STATUS);

        $orderStatusMailTemplate
            ->addVariable(\App\Model\Order\Mail\OrderMail::VARIABLE_NUMBER, t('Order number'))
            ->addVariable(OrderMail::VARIABLE_DATE, t('Date and time of order creation'))
            ->addVariable(OrderMail::VARIABLE_URL, t('E-shop URL address'), MailTemplateVariables::CONTEXT_BODY)
            ->addVariable(OrderMail::VARIABLE_TRANSPORT, t('Chosen shipping name'), MailTemplateVariables::CONTEXT_BODY)
            ->addVariable(OrderMail::VARIABLE_PAYMENT, t('Chosen payment name'), MailTemplateVariables::CONTEXT_BODY)
            ->addVariable(OrderMail::VARIABLE_TOTAL_PRICE, t('Total order price (including VAT)'), MailTemplateVariables::CONTEXT_BODY)
            ->addVariable(OrderMail::VARIABLE_BILLING_ADDRESS, t('Billing address - name, last name, company, company number, tax number and billing address'), MailTemplateVariables::CONTEXT_BODY)
            ->addVariable(OrderMail::VARIABLE_DELIVERY_ADDRESS, t('Delivery address'), MailTemplateVariables::CONTEXT_BODY)
            ->addVariable(OrderMail::VARIABLE_NOTE, t('Note'), MailTemplateVariables::CONTEXT_BODY)
            ->addVariable(OrderMail::VARIABLE_PRODUCTS, t('List of products in order (name, quantity, price per unit including VAT, total price per item including VAT)'), MailTemplateVariables::CONTEXT_BODY)
            ->addVariable(OrderMail::VARIABLE_ORDER_DETAIL_URL, t('Order detail URL address'), MailTemplateVariables::CONTEXT_BODY)
            ->addVariable(OrderMail::VARIABLE_TRANSPORT_INSTRUCTIONS, t('Shipping instructions'), MailTemplateVariables::CONTEXT_BODY)
            ->addVariable(OrderMail::VARIABLE_PAYMENT_INSTRUCTIONS, t('Payment instructions'), MailTemplateVariables::CONTEXT_BODY)
            ->addVariable(OrderMail::VARIABLE_PREPARED_PRODUCTS, t('Seznam ji?? dostupn??ho zbo???? v objedn??vce (n??zev, dostupn?? mno??stv??, cena za jednotku s DPH, celkov?? cena za polo??ku s DPH)'), MailTemplateVariables::CONTEXT_BODY)
            ->addVariable(OrderMail::VARIABLE_TRACKING_URL, t('Odkaz pro sledov??n?? z??silky'), MailTemplateVariables::CONTEXT_BODY)
            ->addVariable(OrderMail::VARIABLE_TRACKING_NUMBER, t('????slo pro sledov??n?? z??silky'), MailTemplateVariables::CONTEXT_BODY);

        $allOrderStatuses = $this->orderStatusFacade->getAll();
        foreach ($allOrderStatuses as $orderStatus) {
            $this->addMailTemplateVariables(
                OrderMail::getMailTemplateNameByStatus($orderStatus),
                $orderStatusMailTemplate->withNewName($orderStatus->getName())
            );
        }
    }
}

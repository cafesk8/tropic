<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate\Mail;

use Shopsys\FrameworkBundle\Model\Mail\MailTemplateVariables;

class OrderGiftCertificateMailTemplateVariablesProvider
{
    /**
     * @return \Shopsys\FrameworkBundle\Model\Mail\MailTemplateVariables
     */
    public function createDefault(): MailTemplateVariables
    {
        return $this->create(t('Dárkový poukaz'));
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Mail\MailTemplateVariables
     */
    public function createActivated(): MailTemplateVariables
    {
        return $this->create(t('Dárkový poukaz - aktivován'));
    }

    /**
     * @param string $templateName
     * @return \Shopsys\FrameworkBundle\Model\Mail\MailTemplateVariables
     */
    private function create(string $templateName): MailTemplateVariables
    {
        $mailTemplateVariables = new MailTemplateVariables($templateName);

        $mailTemplateVariables
            ->addVariable(
                OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_CODE,
                t('Kód dárkového poukazu'),
                MailTemplateVariables::CONTEXT_BODY
            )
            ->addVariable(
                OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_CURRENCY,
                t('Měna dárkového poukazu'),
                MailTemplateVariables::CONTEXT_BODY
            )
            ->addVariable(
                OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_VALID_UNTIL,
                t('Čas konce platnosti poukazu'),
                MailTemplateVariables::CONTEXT_BODY
            )
            ->addVariable(
                OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_VALUE,
                t('Hodnota dárkového poukazu'),
                MailTemplateVariables::CONTEXT_BODY
            )
            ->addVariable(
                OrderGiftCertificateMail::VARIABLE_ORDER_NUMBER,
                t('Číslo objednávky, ve které byl dárkový poukaz zakoupen'),
                MailTemplateVariables::CONTEXT_BODY
            );

        return $mailTemplateVariables;
    }
}

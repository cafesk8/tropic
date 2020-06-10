<?php

declare(strict_types=1);

namespace App\Component\WatchDog;

use Shopsys\FrameworkBundle\Model\Mail\MailTemplateVariables;

class WatchDogMailTemplateVariablesProvider
{
    /**
     * @return \Shopsys\FrameworkBundle\Model\Mail\MailTemplateVariables
     */
    public function create(): MailTemplateVariables
    {
        $mailTemplateVariables = new MailTemplateVariables(t('Hlídač ceny a dostupnosti'));

        $mailTemplateVariables->addVariable(
            WatchDogMail::VARIABLE_PRODUCT_NAME,
            t('Název produktu'),
            MailTemplateVariables::CONTEXT_BODY
        );

        $mailTemplateVariables->addVariable(
            WatchDogMail::VARIABLE_PRODUCT_URL,
            t('Adresa produktu'),
            MailTemplateVariables::CONTEXT_BODY
        );

        return $mailTemplateVariables;
    }
}

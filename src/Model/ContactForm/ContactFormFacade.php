<?php

declare(strict_types=1);

namespace App\Model\ContactForm;

use Shopsys\FrameworkBundle\Model\ContactForm\ContactFormData;
use Shopsys\FrameworkBundle\Model\ContactForm\ContactFormFacade as BaseContactFormFacade;
use Shopsys\FrameworkBundle\Model\Mail\MessageData;

class ContactFormFacade extends BaseContactFormFacade
{
    /**
     * @param \App\Model\ContactForm\ContactFormData $contactFormData
     */
    public function sendMail(ContactFormData $contactFormData): void
    {
        $messageData = new MessageData(
            $this->mailSettingFacade->getMainAdminMail($this->domain->getId()),
            null,
            $this->getMailBody($contactFormData),
            t('Kontaktní formulář - {{ domainName }}', ['{{ domainName }}' => $this->domain->getName()]),
            $contactFormData->email,
            $contactFormData->name
        );
        $this->mailer->send($messageData);
    }

    /**
     * @param \App\Model\ContactForm\ContactFormData $contactFormData
     * @return string
     */
    protected function getMailBody($contactFormData): string
    {
        return $this->twig->render('Mail/ContactForm/mail.html.twig', [
            'contactFormData' => $contactFormData,
        ]);
    }
}

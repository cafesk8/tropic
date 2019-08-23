<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\ContactForm;

use Shopsys\FrameworkBundle\Model\ContactForm\ContactFormData;
use Shopsys\FrameworkBundle\Model\ContactForm\ContactFormFacade as BaseContactFormFacade;
use Shopsys\FrameworkBundle\Model\Mail\MessageData;

class ContactFormFacade extends BaseContactFormFacade
{
    /**
     * @param \Shopsys\ShopBundle\Model\ContactForm\ContactFormData $contactFormData
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
     * @param \Shopsys\ShopBundle\Model\ContactForm\ContactFormData $contactFormData
     * @return string
     */
    protected function getMailBody($contactFormData): string
    {
        return $this->twig->render('@ShopsysShop/Mail/ContactForm/mail.html.twig', [
            'contactFormData' => $contactFormData,
        ]);
    }
}

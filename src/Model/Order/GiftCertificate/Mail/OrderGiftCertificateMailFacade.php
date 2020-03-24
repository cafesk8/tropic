<?php

declare(strict_types=1);

namespace App\Model\Order\GiftCertificate\Mail;

use App\Model\Mail\MailTemplateFacade;
use App\Model\Order\GiftCertificate\OrderGiftCertificate;
use Shopsys\FrameworkBundle\Model\Mail\Mailer;

class OrderGiftCertificateMailFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Mail\Mailer
     */
    private $mailer;

    /**
     * @var \App\Model\Mail\MailTemplateFacade
     */
    private $mailTemplateFacade;

    /**
     * @var \App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMail
     */
    private $orderGiftCertificateMail;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Mail\Mailer $mailer
     * @param \App\Model\Mail\MailTemplateFacade $mailTemplateFacade
     * @param \App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMail $orderGiftCertificateMail
     */
    public function __construct(Mailer $mailer, MailTemplateFacade $mailTemplateFacade, OrderGiftCertificateMail $orderGiftCertificateMail)
    {
        $this->mailer = $mailer;
        $this->mailTemplateFacade = $mailTemplateFacade;
        $this->orderGiftCertificateMail = $orderGiftCertificateMail;
    }

    /**
     * @param \App\Model\Order\GiftCertificate\OrderGiftCertificate $orderGiftCertificate
     * @param string $type
     */
    public function sendGiftCertificateEmail(OrderGiftCertificate $orderGiftCertificate, string $type)
    {
        $mailTemplate = $this->mailTemplateFacade->get(
            $type,
            $orderGiftCertificate->getOrder()->getDomainId()
        );

        if ($mailTemplate->isSendMail()) {
            $messageData = $this->orderGiftCertificateMail->createMessage($mailTemplate, $orderGiftCertificate);
            $this->mailer->send($messageData);
        }
    }
}

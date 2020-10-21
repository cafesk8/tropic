<?php

declare(strict_types=1);

namespace App\Component\Transfer\Mail;

use App\Component\Domain\DomainHelper;
use App\Model\Mail\MailSettingFacade;
use DateTime;
use Shopsys\FrameworkBundle\Model\Mail\Mailer;
use Shopsys\FrameworkBundle\Model\Mail\MessageData;

class TransferMailFacade
{
    private Mailer $mailer;

    private MailSettingFacade $mailSettingFacade;

    private bool $mServerEmailNotificationsEnabled;

    private array $error500Recipients;

    private array $errorTimeoutRecipients;

    /**
     * @param bool $mServerEmailNotificationsEnabled
     * @param string[] $error500Recipients
     * @param string[] $errorTimeoutRecipients
     * @param \Shopsys\FrameworkBundle\Model\Mail\Mailer $mailer
     * @param \App\Model\Mail\MailSettingFacade $mailSettingFacade
     */
    public function __construct(
        bool $mServerEmailNotificationsEnabled,
        array $error500Recipients,
        array $errorTimeoutRecipients,
        Mailer $mailer,
        MailSettingFacade $mailSettingFacade)
    {
        $this->mailer = $mailer;
        $this->mailSettingFacade = $mailSettingFacade;
        $this->error500Recipients = $error500Recipients;
        $this->errorTimeoutRecipients = $errorTimeoutRecipients;
        $this->mServerEmailNotificationsEnabled = $mServerEmailNotificationsEnabled;
    }

    /**
     * @param string $errorMessage
     */
    public function sendMailByErrorMessage(string $errorMessage): void
    {
        if (!$this->mServerEmailNotificationsEnabled) {
            return;
        }
        $recipients = [];
        $body = '';
        $subject = '';
        $lastSent = new DateTime();

        $isError500 = str_contains($errorMessage, '500');
        $isErrorTimeout = str_contains($errorMessage, 'Connection timed out after') || str_contains($errorMessage, 'Failed to connect to');
        $isRelevantError = $isError500 || $isErrorTimeout;
        if ($isError500) {
            $lastSent = $this->mailSettingFacade->getLastSentMserverError500Info();
            $recipients = $this->error500Recipients;
            $subject = 'Chyba mServeru - restartovat';
            $body = ' E-shop zaznamenal problém s připojením na mServer. Zkuste prosím mServer restartovat. Pokud bude problém i nadále přetrvávat, zkontaktujte správce IS Pohoda.';
        } elseif ($isErrorTimeout) {
            $lastSent = $this->mailSettingFacade->getLastSentMserverErrorTimeoutInfo();
            $recipients = $this->errorTimeoutRecipients;
            $subject = 'Chyba mServeru - spustit';
            $body = 'E-shop zaznamenal problém s připojením na mServer. Zkuste prosím mServer spustit. Pokud bude problém i nadále přetrvávat, zkontaktujte správce IS Pohoda.';
        }

        if ($isRelevantError && $this->isLastInfoSentMoreThanHourAgo($lastSent)) {
            $messageData = new MessageData(
                $recipients,
                null,
                $body,
                $subject,
                $this->mailSettingFacade->getMainAdminMail(DomainHelper::CZECH_DOMAIN),
                $this->mailSettingFacade->getMainAdminMailName(DomainHelper::CZECH_DOMAIN)
            );
            $this->mailer->send($messageData);
            if ($isError500) {
                $this->mailSettingFacade->setLastSentMserverError500Info(new DateTime());
            } elseif ($isErrorTimeout) {
                $this->mailSettingFacade->setLastSentMserverErrorTimeoutInfo(new DateTime());
            }
        }
    }

    /**
     * @param \DateTime $lastSent
     * @return bool
     */
    private function isLastInfoSentMoreThanHourAgo(DateTime $lastSent): bool
    {
        $now = new DateTime();
        return $now->getTimestamp() - $lastSent->getTimestamp() >= 3600;
    }
}

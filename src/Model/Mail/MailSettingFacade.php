<?php

declare(strict_types=1);

namespace App\Model\Mail;

use App\Component\Setting\Setting;
use DateTime;
use Shopsys\FrameworkBundle\Model\Mail\Setting\MailSettingFacade as BaseMailSettingFacade;

/**
 * @property \App\Component\Setting\Setting $setting
 * @method __construct(\App\Component\Setting\Setting $setting)
 */
class MailSettingFacade extends BaseMailSettingFacade
{
    /**
     * @return \DateTime
     */
    public function getLastSentMserverError500Info(): DateTime
    {
        return $this->setting->get(Setting::LAST_SENT_M_SERVER_ERROR_500_INFO);
    }

    /**
     * @return \DateTime
     */
    public function getLastSentMserverErrorTimeoutInfo(): DateTime
    {
        return $this->setting->get(Setting::LAST_SENT_M_SERVER_ERROR_TIMEOUT_INFO);
    }

    /**
     * @param \DateTime $dateTime
     */
    public function setLastSentMserverErrorTimeoutInfo(DateTime $dateTime): void
    {
        $this->setting->set(Setting::LAST_SENT_M_SERVER_ERROR_TIMEOUT_INFO, $dateTime);
    }

    /**
     * @param \DateTime $dateTime
     */
    public function setLastSentMserverError500Info(DateTime $dateTime): void
    {
        $this->setting->set(Setting::LAST_SENT_M_SERVER_ERROR_500_INFO, $dateTime);
    }
}

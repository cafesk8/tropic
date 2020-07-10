<?php

declare(strict_types=1);

namespace App\Component\Cron\Config;

use DateTime;
use DateTimeZone;
use Shopsys\FrameworkBundle\Component\Cron\Config\CronModuleConfig as BaseCronModuleConfig;

class CronModuleConfig extends BaseCronModuleConfig
{
    /**
     * This method should shift displayed time to correct timezone for CZ
     *
     * @return string
     */
    public function getReadableFrequency(): string
    {
        $originalHours = $this->timeHours;
        $originalMinutes = $this->timeMinutes;

        if (is_numeric($originalHours)) {
            $minutes = '00';

            if (is_numeric($originalMinutes)) {
                $minutes = $originalMinutes;
            }

            $correctTime = new DateTime(date('Y-m-d ' . $originalHours . ':' . $minutes . ':00'), new DateTimeZone('UTC'));
            $correctTime->setTimezone(new DateTimeZone('Europe/Prague'));
            $this->timeHours = $correctTime->format('H');

            if (is_numeric($originalMinutes)) {
                $this->timeMinutes = $correctTime->format('i');
            }
        }

        $readableFrequency = parent::getReadableFrequency();

        $this->timeHours = $originalHours;
        $this->timeMinutes = $originalMinutes;

        return $readableFrequency;
    }
}

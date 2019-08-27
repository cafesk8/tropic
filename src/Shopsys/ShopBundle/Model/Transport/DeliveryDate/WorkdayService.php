<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport\DeliveryDate;

use DateInterval;
use DateTime;

class WorkdayService
{
    private const SATURDAY = '6';
    private const SUNDAY = '0';

    private const FIXED_HOLIDAYS_BY_LOCALE = [
        'cs' => ['01.01.', '01.05.', '08.05.', '05.07.', '06.07.', '28.09.', '28.10.', '17.11.', '24.12.', '25.12.', '26.12.'],
        'sk' => ['01.01.', '06.01.', '01.05.', '08.05.', '05.07.', '29.08.', '01.09.', '15.09.', '01.11.', '17.11.', '24.12.', '25.12.', '26.12.'],
    ];
    private const HOLIDAYS_FORMAT = 'd.m.';

    /**
     * @param \DateTime $startingDateTime
     * @param int $workdaysCount
     * @param string $locale
     * @return \DateTime
     */
    public function getFirstWorkdayAfterGivenWorkdaysCount(
        DateTime $startingDateTime,
        int $workdaysCount,
        string $locale
    ): DateTime {
        if ($workdaysCount === 0 && $this->isWorkday($startingDateTime, $locale)) {
            return $startingDateTime;
        }

        $finalDateTime = clone $startingDateTime;
        $addedWorkdays = 0;

        do {
            if ($this->isWorkday($finalDateTime, $locale)) {
                $addedWorkdays++;
            }
            $finalDateTime->add(DateInterval::createFromDateString('1 day'));
        } while ($addedWorkdays < $workdaysCount || !$this->isWorkday($finalDateTime, $locale));

        return $finalDateTime;
    }

    /**
     * @param \DateTime $dateTime
     * @param string $locale
     * @return bool
     */
    public function isWorkday(DateTime $dateTime, string $locale): bool
    {
        return !$this->isWeekend($dateTime)
            && !$this->isFixedHoliday($dateTime, $locale)
            && !$this->isEasterHoliday($dateTime);
    }

    /**
     * @param \DateTime $dateTime
     * @return bool
     */
    private function isWeekend(DateTime $dateTime): bool
    {
        return in_array($dateTime->format('w'), [self::SUNDAY, self::SATURDAY], true);
    }

    /**
     * @param \DateTime $dateTime
     * @param string $locale
     * @return bool
     */
    private function isFixedHoliday(DateTime $dateTime, string $locale): bool
    {
        if (!array_key_exists($locale, self::FIXED_HOLIDAYS_BY_LOCALE)) {
            throw new \Shopsys\ShopBundle\Model\Transport\DeliveryDate\Exception\DeliveryDateLocaleNotSupportedException($locale);
        }

        return in_array($dateTime->format(self::HOLIDAYS_FORMAT), self::FIXED_HOLIDAYS_BY_LOCALE[$locale], true);
    }

    /**
     * @param \DateTime $dateTime
     * @return bool
     */
    private function isEasterHoliday(DateTime $dateTime): bool
    {
        $easterFriday = $this->getEasterFridayDateTime($dateTime);
        $easterMonday = $this->getEasterMondayDateTime($dateTime);

        return in_array(
            $dateTime->format(self::HOLIDAYS_FORMAT),
            [
                $easterFriday->format(self::HOLIDAYS_FORMAT),
                $easterMonday->format(self::HOLIDAYS_FORMAT),
            ],
            true
        );
    }

    /**
     * @param \DateTime $currentDateTime
     * @return \DateTime
     */
    private function getEasterFridayDateTime(DateTime $currentDateTime): DateTime
    {
        $easterSunday = $this->getEasterSundayDateTime($currentDateTime);

        return $easterSunday->sub(DateInterval::createFromDateString('2 days'));
    }

    /**
     * @param \DateTime $currentDateTime
     * @return \DateTime
     */
    private function getEasterSundayDateTime(DateTime $currentDateTime): DateTime
    {
        $currentYear = (int)$currentDateTime->format('Y');

        $firstSpringDay = clone $currentDateTime;
        $firstSpringDay->setDate($currentYear, 3, 21);

        $easterDays = easter_days($currentYear);
        $easterSunday = $firstSpringDay->add(DateInterval::createFromDateString($easterDays . ' days'));

        return $easterSunday;
    }

    /**
     * @param \DateTime $currentDateTime
     * @return \DateTime
     */
    private function getEasterMondayDateTime(DateTime $currentDateTime): DateTime
    {
        $easterSunday = $this->getEasterSundayDateTime($currentDateTime);

        return $easterSunday->add(DateInterval::createFromDateString('1 day'));
    }
}

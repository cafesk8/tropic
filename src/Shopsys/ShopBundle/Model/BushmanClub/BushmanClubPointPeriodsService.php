<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\BushmanClub;

use DateTime;

class BushmanClubPointPeriodsService
{
    public const PERIOD_PREVIOUS = 'previous';
    public const PERIOD_ACTUAL = 'actual';

    /**
     * This method calculates previous and actual period based on 2 periods (from, to) and input date
     * Allowed period format is 'm-d' (month-day, e.g 02-01).
     * How does it work
     * 1. create first period and second period with year from input date (BushmanClubPointPeriod::class)
     * 2. find out actual period, in which period input date is in. Update if needed other period to become previous
     * period
     *
     * e.g. if
     *  input date = '01-01-2019'
     *  first period = 08-16 - 02-15
     *  second period = 02-16 - 08 - 15
     *
     * 1. create period with year from input date
     * we get
     *  first period = 08-16-2018 - 02-15-2019
     *  second period = 02-16-2019 - 08 - 15-2019
     *
     * 2. find out actual period
     *   08-16-2018 < 01-01-2019 < 02-15-2019 - input date fits in first period, this is our actual period
     * 2.1 second period is bigger than the first one, we need to edit it and make from it previous period.
     *     subtract one year from second period make it. We get
     *   02-16-2018 - 08 - 15-2018
     *
     * Final result
     * Previous period = 02-16-2018 - 08 - 15-2018
     * Actual period = 08-16-2018 - 02-15-2019
     *
     * This function has limitations, if you do not pass periods which make together whole year. But it costs too much
     * energy to solve this, to be
     *
     * @param string $firstPeriodFrom
     * @param string $firstPeriodTo
     * @param string $secondPeriodFrom
     * @param string $secondPeriodTo
     * @param \DateTime $dateTime
     * @return \Shopsys\ShopBundle\Model\BushmanClub\BushmanClubPointPeriod[]
     */
    public function calculatePreviousAndActualPeriodForDateTime(
        string $firstPeriodFrom,
        string $firstPeriodTo,
        string $secondPeriodFrom,
        string $secondPeriodTo,
        DateTime $dateTime
    ): array {
        $year = $dateTime->format('Y');

        $firstPeriod = new BushmanClubPointPeriod($firstPeriodFrom, $firstPeriodTo, $year);
        $secondPeriod = new BushmanClubPointPeriod($secondPeriodFrom, $secondPeriodTo, $year);

        $periods[self::PERIOD_PREVIOUS] = $firstPeriod;
        $periods[self::PERIOD_ACTUAL] = $secondPeriod;

        if (($firstPeriod->getDateFrom() <= $dateTime) && ($dateTime < $firstPeriod->getDateTo())) {
            $secondPeriod->getDateTo()->modify('-1 year');
            $secondPeriod->getDateFrom()->modify('-1 year');

            $periods[self::PERIOD_PREVIOUS] = $secondPeriod;
            $periods[self::PERIOD_ACTUAL] = $firstPeriod;
        } elseif (($secondPeriod->getDateFrom() <= $dateTime) && ($dateTime < $secondPeriod->getDateTo())) {
            $periods[self::PERIOD_PREVIOUS] = $firstPeriod;
            $periods[self::PERIOD_ACTUAL] = $secondPeriod;
        } elseif ($secondPeriod->getDateTo() <= $dateTime) {
            $firstPeriod->getDateTo()->modify('+1 year');
            $firstPeriod->getDateFrom()->modify('+1 year');

            $periods[self::PERIOD_PREVIOUS] = $secondPeriod;
            $periods[self::PERIOD_ACTUAL] = $firstPeriod;
        }

        return $periods;
    }
}

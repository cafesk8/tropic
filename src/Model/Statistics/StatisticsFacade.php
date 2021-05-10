<?php

declare(strict_types=1);

namespace App\Model\Statistics;

use DateInterval;
use DateTime;
use Shopsys\FrameworkBundle\Model\Statistics\StatisticsFacade as BaseStatisticsFacade;

/**
 * @property \App\Model\Statistics\ValueByDateTimeDataPointFormatter $valueByDateTimeDataPointFormatter
 * @property \App\Model\Statistics\StatisticsRepository $statisticsRepository
 * @method __construct(\App\Model\Statistics\StatisticsRepository $statisticsRepository, \App\Model\Statistics\ValueByDateTimeDataPointFormatter $valueByDateTimeDataPointFormatter)
 */
class StatisticsFacade extends BaseStatisticsFacade
{
    /**
     * @return \App\Model\Statistics\OrderValueByDateTimeDataPoint[]
     */
    public function getNewOrdersCountByDayInLastTwoWeeks(): array
    {
        $startDataTime = new DateTime('- 2 weeks midnight');
        $tomorrowDateTime = new DateTime('tomorrow');

        $valueByDateTimeDataPoints = $this->statisticsRepository->getNewOrdersCountByDayBetweenTwoDateTimes(
            $startDataTime,
            $tomorrowDateTime
        );
        /** @var \App\Model\Statistics\OrderValueByDateTimeDataPoint[] $dataPoints */
        $dataPoints = $this->valueByDateTimeDataPointFormatter->normalizeDataPointsByDateTimeIntervals(
            $valueByDateTimeDataPoints,
            $startDataTime,
            $tomorrowDateTime,
            DateInterval::createFromDateString('+ 1 day'),
            OrderValueByDateTimeDataPoint::class
        );

        return $dataPoints;
    }
}

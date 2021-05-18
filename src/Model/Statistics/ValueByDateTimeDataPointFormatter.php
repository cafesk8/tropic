<?php

declare(strict_types=1);

namespace App\Model\Statistics;

use DateInterval;
use DateTime;
use Shopsys\FrameworkBundle\Model\Statistics\ValueByDateTimeDataPoint;
use Shopsys\FrameworkBundle\Model\Statistics\ValueByDateTimeDataPointFormatter as BaseValueByDateTimeDataPointFormatter;

/**
 * @property \App\Twig\DateTimeFormatterExtension $dateTimeFormatterExtension
 * @method __construct(\App\Twig\DateTimeFormatterExtension $dateTimeFormatterExtension)
 */
class ValueByDateTimeDataPointFormatter extends BaseValueByDateTimeDataPointFormatter
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Statistics\ValueByDateTimeDataPoint[] $valueByDateTimeDataPoints
     * @param \DateTime $startDateTime
     * @param \DateTime $endDateTime
     * @param \DateInterval $interval
     * @param string|null $type
     * @return \Shopsys\FrameworkBundle\Model\Statistics\ValueByDateTimeDataPoint[]|\App\Model\Statistics\OrderValueByDateTimeDataPoint[]
     */
    public function normalizeDataPointsByDateTimeIntervals(
        array $valueByDateTimeDataPoints,
        DateTime $startDateTime,
        DateTime $endDateTime,
        DateInterval $interval,
        ?string $type = ValueByDateTimeDataPoint::class
    ): array {
        /** @var \Shopsys\FrameworkBundle\Model\Statistics\ValueByDateTimeDataPoint[] $normalizedDataPoints */
        $normalizedDataPoints = parent::normalizeDataPointsByDateTimeIntervals($valueByDateTimeDataPoints, $startDateTime, $endDateTime, $interval);

        foreach ($normalizedDataPoints as $index => $normalizedDataPoint) {
            switch ($type) {
                case OrderValueByDateTimeDataPoint::class:
                    if (!($normalizedDataPoint instanceof OrderValueByDateTimeDataPoint)) {
                        $normalizedDataPoints[$index] = new OrderValueByDateTimeDataPoint($normalizedDataPoint->getValue(), $normalizedDataPoint->getDateTime(), '0');
                    }
                    break;
            }
        }

        return $normalizedDataPoints;
    }
}

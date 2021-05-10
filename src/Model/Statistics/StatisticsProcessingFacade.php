<?php

declare(strict_types=1);

namespace App\Model\Statistics;

use Shopsys\FrameworkBundle\Model\Statistics\StatisticsProcessingFacade as BaseStatisticsProcessingFacade;

/**
 * @property \App\Model\Statistics\ValueByDateTimeDataPointFormatter $valueByDateTimeDataPointFormatter
 * @method __construct(\App\Model\Statistics\ValueByDateTimeDataPointFormatter $valueByDateTimeDataPointFormatter)
 */
class StatisticsProcessingFacade extends BaseStatisticsProcessingFacade
{
    /**
     * @param \App\Model\Statistics\OrderValueByDateTimeDataPoint[] $valueByDateTimeDataPoints
     * @return string[]
     */
    public function getSums(array $valueByDateTimeDataPoints): array
    {
        return array_map(fn (OrderValueByDateTimeDataPoint $valueByDateTimeDataPoint) => $valueByDateTimeDataPoint->getSum(), $valueByDateTimeDataPoints);
    }
}

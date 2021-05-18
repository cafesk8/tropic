<?php

declare(strict_types=1);

namespace App\Model\Statistics;

use DateTime;
use Shopsys\FrameworkBundle\Model\Statistics\ValueByDateTimeDataPoint;

class OrderValueByDateTimeDataPoint extends ValueByDateTimeDataPoint
{
    private string $sum;

    /**
     * @param mixed $count
     * @param \DateTime $dateTime
     * @param string $sum
     */
    public function __construct($count, DateTime $dateTime, string $sum)
    {
        parent::__construct($count, $dateTime);
        $this->sum = $sum;
    }

    /**
     * @return string
     */
    public function getSum(): string
    {
        return $this->sum;
    }
}

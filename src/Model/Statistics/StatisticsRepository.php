<?php

declare(strict_types=1);

namespace App\Model\Statistics;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\ResultSetMapping;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatus;
use Shopsys\FrameworkBundle\Model\Statistics\StatisticsRepository as BaseStatisticsRepository;

class StatisticsRepository extends BaseStatisticsRepository
{
    private const TIMEZONE_UTC = 'UTC';

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @return \App\Model\Statistics\OrderValueByDateTimeDataPoint[]
     */
    public function getNewOrdersCountByDayBetweenTwoDateTimes(DateTime $start, DateTime $end): array
    {
        $actualTimezone = new DateTimeZone('Europe/Prague');
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('count', 'count');
        $resultSetMapping->addScalarResult('sum', 'sum');
        $resultSetMapping->addScalarResult('date', 'date', Types::DATETIME_MUTABLE);

        $query = $this->em->createNativeQuery(
            'SELECT DATE(o.created_at AT TIME ZONE :timezone_utc AT TIME ZONE :timezone_actual) AS date, 
                        COUNT(o.created_at AT TIME ZONE :timezone_utc AT TIME ZONE :timezone_actual) AS count,
                        ROUND(SUM(o.total_price_with_vat * c.exchange_rate)) AS sum
            FROM orders o
            JOIN currencies c ON c.id = o.currency_id
            WHERE (o.created_at AT TIME ZONE :timezone_utc AT TIME ZONE :timezone_actual) 
                BETWEEN (:start_date AT TIME ZONE :timezone_utc AT TIME ZONE :timezone_actual) AND (:end_date AT TIME ZONE :timezone_utc AT TIME ZONE :timezone_actual) 
                AND o.status_id != :canceled
                AND o.deleted = FALSE
            GROUP BY date
            ORDER BY date',
            $resultSetMapping
        );

        $query->setParameter('start_date', $start);
        $query->setParameter('end_date', $end);
        $query->setParameter('canceled', OrderStatus::TYPE_CANCELED);
        $query->setParameter('timezone_utc', self::TIMEZONE_UTC);
        $query->setParameter('timezone_actual', $actualTimezone->getName());

        return array_map(fn (array $item) => new OrderValueByDateTimeDataPoint($item['count'], $item['date'], $item['sum']), $query->getResult());
    }
}

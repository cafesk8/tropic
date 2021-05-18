<?php

declare(strict_types=1);

namespace App\Model\Statistics;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\ResultSetMapping;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatus;
use Shopsys\FrameworkBundle\Model\Statistics\StatisticsRepository as BaseStatisticsRepository;

class StatisticsRepository extends BaseStatisticsRepository
{
    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @return \App\Model\Statistics\OrderValueByDateTimeDataPoint[]
     */
    public function getNewOrdersCountByDayBetweenTwoDateTimes(DateTime $start, DateTime $end): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('count', 'count');
        $resultSetMapping->addScalarResult('sum', 'sum');
        $resultSetMapping->addScalarResult('date', 'date', Types::DATE_MUTABLE);

        $query = $this->em->createNativeQuery(
            'SELECT DATE(o.created_at) AS date, COUNT(o.created_at) AS count, ROUND(SUM(o.total_price_with_vat * c.exchange_rate)) AS sum
            FROM orders o
            JOIN currencies c ON c.id = o.currency_id
            WHERE o.created_at BETWEEN :start_date AND :end_date AND o.status_id != :canceled AND o.deleted = FALSE
            GROUP BY date
            ORDER BY date',
            $resultSetMapping
        );

        $query->setParameter('start_date', $start);
        $query->setParameter('end_date', $end);
        $query->setParameter('canceled', OrderStatus::TYPE_CANCELED);

        return array_map(fn (array $item) => new OrderValueByDateTimeDataPoint($item['count'], $item['date'], $item['sum']), $query->getResult());
    }
}

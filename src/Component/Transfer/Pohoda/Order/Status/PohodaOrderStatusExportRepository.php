<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Order\Status;

use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use App\Component\Transfer\Pohoda\Helpers\PohodaDateTimeHelper;
use DateTimeZone;
use Doctrine\ORM\Query\ResultSetMapping;

class PohodaOrderStatusExportRepository
{
    private const POHODA_ORDER_STATUS_AGENDA_ID = 2;

    private PohodaEntityManager $pohodaEntityManager;

    /**
     * @param \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager $pohodaEntityManager
     */
    public function __construct(PohodaEntityManager $pohodaEntityManager)
    {
        $this->pohodaEntityManager = $pohodaEntityManager;
    }

    /**
     * @param \DateTime|null $lastUpdateTime
     * @return array
     */
    public function getPohodaOrderIdsByLastUpdateTime(?\DateTime $lastUpdateTime): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('ID', PohodaOrderStatus::COL_POHODA_ORDER_ID);

        if ($lastUpdateTime !== null) {
            // Timezone in Pohoda is always Europe/Prague and we store dates in UTC so we need to convert the last update time to Pohoda´s timezone
            $lastUpdateTime->setTimezone(new DateTimeZone('Europe/Prague'));
        }

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT O.ID
            FROM OBJ O
            WHERE O.DatSave >= :lastUpdateDateTime
                AND O.RefVprStavObj IS NOT NULL
            ORDER BY O.DatSave',
            $resultSetMapping
        )
            ->setParameters([
                'lastUpdateDateTime' => $lastUpdateTime === null ? PohodaDateTimeHelper::FIRST_UPDATE_TIME : $lastUpdateTime->format(PohodaDateTimeHelper::DATE_TIME_FORMAT),
            ]);

        return $query->getResult();
    }

    /**
     * @param int[] $pohodaOrderIds
     * @return array
     */
    public function getByPohodaOrderIds(array $pohodaOrderIds): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('orderId', PohodaOrderStatus::COL_POHODA_ORDER_ID)
            ->addScalarResult('statusId', PohodaOrderStatus::COL_POHODA_STATUS_ID)
            ->addScalarResult('IDS', PohodaOrderStatus::COL_POHODA_STATUS_NAME);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT O.ID AS orderId, OrderStatus.ID AS statusId, OrderStatus.IDS
            FROM OBJ O
            JOIN sVPULpol OrderStatus ON OrderStatus.ID = O.RefVprStavObj
            WHERE O.ID IN(:orderIds)
                AND OrderStatus.RefAg = :orderStatusAgendaId
            ORDER BY O.DatSave',
            $resultSetMapping
        )
            ->setParameters([
                'orderIds' => $pohodaOrderIds,
                'orderStatusAgendaId' => self::POHODA_ORDER_STATUS_AGENDA_ID,
            ]);

        return $query->getResult();
    }
}

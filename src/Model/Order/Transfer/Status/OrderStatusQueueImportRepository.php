<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer\Status;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class OrderStatusQueueImportRepository
{
    private EntityManagerInterface $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param int[] $pohodaOrderIds
     * @param \DateTime $pohodaTransferDateTime
     */
    public function insertChangedPohodaOrderIds(array $pohodaOrderIds, \DateTime $pohodaTransferDateTime): void
    {
        foreach ($pohodaOrderIds as $pohodaOrderId) {
            $query = $this->em->createNativeQuery(
                'INSERT INTO pohoda_changed_order_statuses_queue (pohoda_id, inserted_at)
                VALUES (:pohoda_id, :inserted_at) ON CONFLICT DO NOTHING',
                new ResultSetMapping()
            );

            $query->execute([
                'pohoda_id' => $pohodaOrderId,
                'inserted_at' => $pohodaTransferDateTime,
            ]);
        }
    }

    /**
     * @param int $limit
     * @return array
     */
    public function getChangedPohodaOrderIds(int $limit): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('pohoda_id', 'pohodaId');

        $queryBuilder = $this->em->createNativeQuery(
            'SELECT pohoda_id
            FROM pohoda_changed_order_statuses_queue
            ORDER BY inserted_at
            LIMIT :ordersLimit',
            $resultSetMapping
        );

        $queryBuilder->setParameters([
            'ordersLimit' => $limit,
        ]);

        return $queryBuilder->getArrayResult();
    }

    /**
     * @return bool
     */
    public function isQueueEmpty(): bool
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('orders_count', 'ordersCount');

        $queryBuilder = $this->em->createNativeQuery(
            'SELECT COUNT(1) AS orders_count
            FROM pohoda_changed_order_statuses_queue',
            $resultSetMapping
        );

        return $queryBuilder->getSingleScalarResult() === 0;
    }

    /**
     * @param array $updatedPohodaOrderIds
     */
    public function removeOrdersFromQueue(array $updatedPohodaOrderIds): void
    {
        $queryBuilder = $this->em->createNativeQuery(
            'DELETE FROM pohoda_changed_order_statuses_queue
            WHERE pohoda_id IN(:updatedOrderIds)',
            new ResultSetMapping()
        );

        $queryBuilder->execute([
            'updatedOrderIds' => $updatedPohodaOrderIds,
        ]);
    }
}

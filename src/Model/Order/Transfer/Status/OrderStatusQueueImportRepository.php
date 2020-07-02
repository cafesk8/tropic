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
}

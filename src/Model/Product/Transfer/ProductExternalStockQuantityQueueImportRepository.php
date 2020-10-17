<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class ProductExternalStockQuantityQueueImportRepository
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
     * @param array $pohodaProductIds
     * @param \DateTime $pohodaTransferDateTime
     */
    public function insertChangedPohodaProductIds(array $pohodaProductIds, \DateTime $pohodaTransferDateTime): void
    {
        $pohodaTransferDateTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        foreach ($pohodaProductIds as $pohodaProductId) {
            $query = $this->em->createNativeQuery(
                'INSERT INTO pohoda_products_external_stock_quantity_queue (pohoda_product_id, inserted_at)
                VALUES (:pohodaProductId, :insertedAt) ON CONFLICT DO NOTHING',
                new ResultSetMapping()
            );

            $query->execute([
                'pohodaProductId' => $pohodaProductId,
                'insertedAt' => $pohodaTransferDateTime,
            ]);
        }
    }

    /**
     * @param int $limit
     * @return int[]
     */
    public function getChangedPohodaProductIds(int $limit): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('pohoda_product_id', 'pohodaId');

        $queryBuilder = $this->em->createNativeQuery(
            'SELECT pohoda_product_id
            FROM pohoda_products_external_stock_quantity_queue
            ORDER BY inserted_at
            LIMIT :productsLimit',
            $resultSetMapping
        );

        $queryBuilder->setParameters([
            'productsLimit' => $limit,
        ]);

        return $queryBuilder->getArrayResult();
    }

    /**
     * @param array $updatedPohodaProductIds
     */
    public function removeProductsFromQueue(array $updatedPohodaProductIds): void
    {
        $queryBuilder = $this->em->createNativeQuery(
            'DELETE FROM pohoda_products_external_stock_quantity_queue
            WHERE pohoda_product_id IN(:updatedProducts)',
            new ResultSetMapping()
        );

        $queryBuilder->execute([
            'updatedProducts' => $updatedPohodaProductIds,
        ]);
    }

    /**
     * @return bool
     */
    public function isQueueEmpty(): bool
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('product_count', 'productCount');

        $queryBuilder = $this->em->createNativeQuery(
            'SELECT COUNT(1) AS product_count
            FROM pohoda_products_external_stock_quantity_queue',
            $resultSetMapping
        );

        return $queryBuilder->getSingleScalarResult() === 0;
    }
}

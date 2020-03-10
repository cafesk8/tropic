<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class ProductInfoQueueImportRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

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
        foreach ($pohodaProductIds as $pohodaProductId) {
            $query = $this->em->createNativeQuery(
                'INSERT INTO pohoda_changed_products_basic_info_queue (pohoda_id, inserted_at)
                VALUES (:pohoda_id, :inserted_at) ON CONFLICT DO NOTHING',
                new ResultSetMapping()
            );

            $query->execute([
                'pohoda_id' => $pohodaProductId,
                'inserted_at' => $pohodaTransferDateTime,
            ]);
        }
    }

    /**
     * @param int $limit
     * @return int[]
     */
    public function findChangedPohodaProductIds(int $limit): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('pohoda_id', 'pohodaId');

        $queryBuilder = $this->em->createNativeQuery(
            'SELECT pohoda_id
            FROM pohoda_changed_products_basic_info_queue
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
     * @return bool
     */
    public function isQueueEmpty(): bool
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('product_count', 'productCount');

        $queryBuilder = $this->em->createNativeQuery(
            'SELECT COUNT(1) AS product_count
            FROM pohoda_changed_products_basic_info_queue',
            $resultSetMapping
        );

        return $queryBuilder->getSingleScalarResult() === 0;
    }

    /**
     * @param array $updatedPohodaProductIds
     */
    public function removeUpdatedProducts(array $updatedPohodaProductIds): void
    {
        $queryBuilder = $this->em->createNativeQuery(
            'DELETE FROM pohoda_changed_products_basic_info_queue
            WHERE pohoda_id IN(:updatedProducts)',
            new ResultSetMapping()
        );

        $queryBuilder->execute([
            'updatedProducts' => $updatedPohodaProductIds,
        ]);
    }
}

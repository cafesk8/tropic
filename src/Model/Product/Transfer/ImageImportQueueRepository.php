<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class ImageImportQueueRepository
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
     * @param int[] $pohodaProductIds
     */
    public function insertChangedPohodaProductIds(array $pohodaProductIds): void
    {
        $timezone = new \DateTimeZone(date_default_timezone_get());

        foreach ($pohodaProductIds as $pohodaProductId) {
            $query = $this->em->createNativeQuery(
                'INSERT INTO pohoda_images_import_queue (pohoda_product_id, inserted_at)
                VALUES (:pohodaProductId, :insertedAt) ON CONFLICT DO NOTHING',
                new ResultSetMapping()
            );

            $query->execute([
                'pohodaProductId' => $pohodaProductId,
                'insertedAt' => new \DateTime('now', $timezone),
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
        $resultSetMapping->addScalarResult('pohoda_product_id', 'pohodaId');

        $queryBuilder = $this->em->createNativeQuery(
            'SELECT pohoda_product_id
            FROM pohoda_images_import_queue
            ORDER BY inserted_at
            LIMIT :productsLimit',
            $resultSetMapping
        );

        $queryBuilder->setParameters([
            'productsLimit' => $limit,
        ]);

        return array_column($queryBuilder->getArrayResult(), 'pohodaId');
    }

    /**
     * @param int[] $updatedPohodaProductIds
     */
    public function removeUpdatedProducts(array $updatedPohodaProductIds): void
    {
        $queryBuilder = $this->em->createNativeQuery(
            'DELETE FROM pohoda_images_import_queue
            WHERE pohoda_product_id IN (:updatedProducts)',
            new ResultSetMapping()
        );

        $queryBuilder->execute([
            'updatedProducts' => $updatedPohodaProductIds,
        ]);
    }

    /**
     * @param int $pohodaId
     */
    public function rescheduleImageImport(int $pohodaId): void
    {
        $timezone = new \DateTimeZone(date_default_timezone_get());

        $query = $this->em->createNativeQuery(
            'UPDATE pohoda_images_import_queue SET inserted_at = :insertedAt WHERE pohoda_product_id = :pohodaProductId',
            new ResultSetMapping()
        );

        $query->execute([
            'pohodaProductId' => $pohodaId,
            'insertedAt' => new \DateTime('now', $timezone),
        ]);
    }
}
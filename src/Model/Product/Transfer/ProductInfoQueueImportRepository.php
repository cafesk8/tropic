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
        $pohodaTransferDateTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));

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
            'SELECT Q.pohoda_id
            FROM pohoda_changed_products_basic_info_queue Q
            WHERE Q.pohoda_id NOT IN (
                SELECT P.pohoda_id FROM products P 
                    JOIN order_items OI ON P.id = OI.product_id 
                    JOIN orders O ON OI.order_id = O.id 
                WHERE O.export_status != \'export_success\' AND O.created_at > NOW() - interval \'1 hour\'
            )
            ORDER BY Q.inserted_at
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

    /**
     * @param array $pohodaProductIds
     */
    public function moveProductsToEndOfQueue(array $pohodaProductIds): void
    {
        $queryBuilder = $this->em->createNativeQuery(
            'UPDATE pohoda_changed_products_basic_info_queue
            SET inserted_at = :insertedAtNow
            WHERE pohoda_id IN(:updatedProducts)',
            new ResultSetMapping()
        );

        $queryBuilder->execute([
            'insertedAtNow' => new \DateTime(),
            'updatedProducts' => $pohodaProductIds,
        ]);
    }
}

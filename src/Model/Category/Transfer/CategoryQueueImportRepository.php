<?php

declare(strict_types=1);

namespace App\Model\Category\Transfer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class CategoryQueueImportRepository
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
     * @param array $pohodaCategoryIds
     * @param \DateTime $pohodaTransferDateTime
     */
    public function insertChangedPohodaCategoryIds(array $pohodaCategoryIds, \DateTime $pohodaTransferDateTime): void
    {
        foreach ($pohodaCategoryIds as $pohodaCategoryId) {
            $query = $this->em->createNativeQuery(
                'INSERT INTO pohoda_changed_categories_queue (pohoda_id, inserted_at)
                VALUES (:pohoda_id, :inserted_at) ON CONFLICT DO NOTHING',
                new ResultSetMapping()
            );

            $query->execute([
                'pohoda_id' => $pohodaCategoryId,
                'inserted_at' => $pohodaTransferDateTime,
            ]);
        }
    }

    /**
     * @param int $limit
     * @return int[]
     */
    public function findChangedPohodaCategoryIds(int $limit): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('pohoda_id', 'pohodaId');

        $queryBuilder = $this->em->createNativeQuery(
            'SELECT pohoda_id
            FROM pohoda_changed_categories_queue
            ORDER BY inserted_at
            LIMIT :categoriesLimit',
            $resultSetMapping
        );

        $queryBuilder->setParameters([
            'categoriesLimit' => $limit,
        ]);

        return $queryBuilder->getArrayResult();
    }

    /**
     * @return bool
     */
    public function isQueueEmpty(): bool
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('categories_count', 'categoriesCount');

        $queryBuilder = $this->em->createNativeQuery(
            'SELECT COUNT(1) AS categories_count
            FROM pohoda_changed_categories_queue',
            $resultSetMapping
        );

        return $queryBuilder->getSingleScalarResult() === 0;
    }

    /**
     * @param array $updatedPohodaCategoryIds
     */
    public function removeUpdatedCategories(array $updatedPohodaCategoryIds): void
    {
        $queryBuilder = $this->em->createNativeQuery(
            'DELETE FROM pohoda_changed_categories_queue
            WHERE pohoda_id IN(:updatedCategories)',
            new ResultSetMapping()
        );

        $queryBuilder->execute([
            'updatedCategories' => $updatedPohodaCategoryIds,
        ]);
    }
}

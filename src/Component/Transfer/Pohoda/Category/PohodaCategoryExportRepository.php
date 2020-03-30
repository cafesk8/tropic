<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Category;

use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use App\Component\Transfer\Pohoda\Helpers\PohodaDateTimeHelper;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\Query\ResultSetMapping;

class PohodaCategoryExportRepository
{
    /**
     * @var \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager
     */
    private $pohodaEntityManager;

    /**
     * @param \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager $pohodaEntityManager
     */
    public function __construct(PohodaEntityManager $pohodaEntityManager)
    {
        $this->pohodaEntityManager = $pohodaEntityManager;
    }

    /**
     * @param int[] $pohodaCategoryIds
     * @return array
     */
    public function getByPohodaCategoryIds(array $pohodaCategoryIds): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('ID', PohodaCategory::COL_POHODA_ID)
            ->addScalarResult('IDS', PohodaCategory::COL_NAME)
            ->addScalarResult('Pozn', PohodaCategory::COL_NAME_SK)
            ->addScalarResult('RefNodeID', PohodaCategory::COL_PARENT_ID)
            ->addScalarResult('Poradi', PohodaCategory::COL_POSITION)
            ->addScalarResult('Zobraz', PohodaCategory::COL_NOT_LISTABLE)
            ->addScalarResult('Node', PohodaCategory::COL_LEVEL);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT ID, IDS, Pozn, RefNodeID, Poradi, Zobraz, Node
            FROM SkKat Category
            WHERE Category.ID IN(:categoryIds)
            ORDER BY Category.Poradi, Category.RefNodeID, Category.DatSave',
            $resultSetMapping
        )
            ->setParameters([
                'categoryIds' => $pohodaCategoryIds,
            ]);

        return $query->getResult();
    }

    /**
     * @param \DateTime|null $lastUpdateTime
     * @return array
     */
    public function getPohodaCategoryIdsByLastUpdateTime(?DateTime $lastUpdateTime): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('ID', PohodaCategory::COL_POHODA_ID);

        if ($lastUpdateTime !== null) {
            // Timezone in Pohoda is always Europe/Prague and we store dates in UTC so we need to convert the last update time to Pohoda´s timezone
            $lastUpdateTime->setTimezone(new DateTimeZone('Europe/Prague'));
        }

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT ID
            FROM SkKat Category
            WHERE DatSave > :lastUpdateDateTime 
            ORDER BY Category.Poradi, Category.RefNodeID, Category.DatSave',
            $resultSetMapping
        )
            ->setParameters([
                'lastUpdateDateTime' => $lastUpdateTime === null ? PohodaDateTimeHelper::FIRST_UPDATE_TIME : $lastUpdateTime->format(PohodaDateTimeHelper::DATE_TIME_FORMAT),
            ]);

        return $query->getResult();
    }

    /**
     * @return array
     */
    public function getAllPohodaIds(): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('ID', PohodaCategory::COL_POHODA_ID);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT ID
            FROM SkKat Category',
            $resultSetMapping
        );

        return $query->getScalarResult();
    }
}

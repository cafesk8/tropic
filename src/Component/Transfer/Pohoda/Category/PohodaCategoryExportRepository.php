<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Category;

use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
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
     * @return array
     */
    public function findAll(): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('ID', PohodaCategory::COL_POHODA_ID)
            ->addScalarResult('IDS', PohodaCategory::COL_NAME)
            ->addScalarResult('Pozn', PohodaCategory::COL_NAME_SK)
            ->addScalarResult('RefNodeID', PohodaCategory::COL_PARENT_ID)
            ->addScalarResult('Poradi', PohodaCategory::COL_POSITION)
            ->addScalarResult('Zobraz', PohodaCategory::COL_LISTABLE)
            ->addScalarResult('Node', PohodaCategory::COL_LEVEL);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT ID, IDS, Pozn, RefNodeID, Poradi, Zobraz, Node
             FROM SkKat Category
             ORDER BY Category.DatSave',
            $resultSetMapping
        );

        return $query->getResult();
    }
}

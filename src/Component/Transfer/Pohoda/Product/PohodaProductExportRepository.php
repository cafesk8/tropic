<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product;

use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;

class PohodaProductExportRepository
{
    private const FIRST_UPDATE_TIME = '2000-01-01 00:00:00';

    public const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public const DEFAULT_POHODA_STOCK_ID = self::POHODA_STOCK_TROPIC_ID;

    public const POHODA_STOCK_TROPIC_ID = 10;

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
     * @param array $pohodaProductIds
     * @return array
     */
    public function findByPohodaProductIds(array $pohodaProductIds): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('ID', PohodaProduct::COL_POHODA_ID)
            ->addScalarResult('IDS', PohodaProduct::COL_CATNUM)
            ->addScalarResult('Nazev', PohodaProduct::COL_NAME)
            ->addScalarResult('Nazev1', PohodaProduct::COL_NAME_SK)
            ->addScalarResult('Popis', PohodaProduct::COL_SHORT_DESCRIPTION)
            ->addScalarResult('Popis2', PohodaProduct::COL_LONG_DESCRIPTION);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT Product.ID, Product.IDS, Product.Nazev, Product.Nazev1, Product.Popis, Product.Popis2 
             FROM Skz Product
             WHERE Product.ID IN (:pohodaProductIds)
                AND Product.IObchod = 1
             ORDER BY Product.DatSave',
            $resultSetMapping
        )
            ->setParameters([
                'pohodaProductIds' => $pohodaProductIds,
            ]);

        return $query->getResult();
    }

    /**
     * @param \DateTime|null $lastUpdateTime
     * @param int $limit
     * @return array
     */
    public function findProductPohodaIdsByLastUpdateTime(?DateTime $lastUpdateTime): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('ID', PohodaProduct::COL_POHODA_ID);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT Product.ID
            FROM Skz Product
            WHERE Product.RefSklad = :defaultStockId 
                AND Product.IDS IN (
                    SELECT IDS
                    FROM SKz 
                    WHERE DatSave > :lastUpdateDateTime
                    GROUP BY IDS
                )
                AND Product.IObchod = 1
            ORDER BY Product.DatSave',
            $resultSetMapping
        )
            ->setParameters([
                'defaultStockId' => self::DEFAULT_POHODA_STOCK_ID,
                'lastUpdateDateTime' => $lastUpdateTime === null ? self::FIRST_UPDATE_TIME : $lastUpdateTime->format(self::DATE_TIME_FORMAT),
            ]);

        return $query->getResult();
    }


}

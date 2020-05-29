<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product;

use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use App\Component\Transfer\Pohoda\Helpers\PohodaDateTimeHelper;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\Query\ResultSetMapping;

class PohodaProductExportRepository
{
    public const DEFAULT_POHODA_STOCK_ID = self::POHODA_STOCK_TROPIC_ID;

    public const POHODA_STOCK_SALE_ID = 2;

    public const POHODA_STOCK_STORE_ID = 4;

    public const POHODA_STOCK_TROPIC_ID = 10;

    public const POHODA_STOCK_EXTERNAL_ID = 11;

    public const POHODA_STOCK_STORE_SALE_ID = 13;

    public const SALE_STOCK_IDS_ORDERED_BY_PRIORITY = [
        self::POHODA_STOCK_SALE_ID,
        self::POHODA_STOCK_STORE_SALE_ID,
    ];

    public const PRODUCT_STOCK_IDS = [
        self::POHODA_STOCK_SALE_ID,
        self::POHODA_STOCK_STORE_ID,
        self::POHODA_STOCK_TROPIC_ID,
        self::POHODA_STOCK_STORE_SALE_ID,
    ];

    private const PRODUCT_TYPE_GROUP_ID = 5;

    private const POHODA_PRODUCT_COLUMN_ALIASES = [
        'ID' => PohodaProduct::COL_POHODA_ID,
        'IDS' => PohodaProduct::COL_CATNUM,
        'Nazev' => PohodaProduct::COL_NAME,
        'Nazev1' => PohodaProduct::COL_NAME_SK,
        'Popis' => PohodaProduct::COL_SHORT_DESCRIPTION,
        'Popis2' => PohodaProduct::COL_LONG_DESCRIPTION,
        'VPrVyjmProdSl' => PohodaProduct::COL_REGISTRATION_DISCOUNT_DISABLED,
        'VPrVyjmProdAkc' => PohodaProduct::COL_PROMO_DISCOUNT_DISABLED,
        'ProdejDPH' => PohodaProduct::COL_SELLING_PRICE,
        'RelDPHp' => PohodaProduct::COL_SELLING_VAT_RATE_ID,
        'NakupDPH' => PohodaProduct::COL_PURCHASE_PRICE,
        'VPrBCena' => PohodaProduct::COL_STANDARD_PRICE,
        'ObjNazev' => PohodaProduct::COL_VARIANT_ID,
        'SText' => PohodaProduct::COL_VARIANT_ALIAS,
        'SText1' => PohodaProduct::COL_VARIANT_ALIAS_SK,
        'RelSkTyp' => PohodaProduct::COL_POHODA_PRODUCT_TYPE,
        'VPrAutPrepEUR' => PohodaProduct::COL_AUTO_EUR_PRICE,
        'Dodani' => PohodaProduct::COL_DELIVERY_DAYS,
        'VPrNovinkaOd' => PohodaProduct::COL_FLAG_NEW_FROM,
        'VPrNovinkaDo' => PohodaProduct::COL_FLAG_NEW_TO,
        'VPrDoprodejOd' => PohodaProduct::COL_FLAG_CLEARANCE_FROM,
        'VPrDoprodejDo' => PohodaProduct::COL_FLAG_CLEARANCE_TO,
        'VPrAkceOd' => PohodaProduct::COL_FLAG_ACTION_FROM,
        'VPrAkceDo' => PohodaProduct::COL_FLAG_ACTION_TO,
        'VPrDoporOd' => PohodaProduct::COL_FLAG_RECOMMENDED_FROM,
        'VPrDoporDo' => PohodaProduct::COL_FLAG_RECOMMENDED_TO,
        'VPrSlevaOd' => PohodaProduct::COL_FLAG_DISCOUNT_FROM,
        'VPrSlevaDo' => PohodaProduct::COL_FLAG_DISCOUNT_TO,
        'VPrPripravOd' => PohodaProduct::COL_FLAG_PREPARATION_FROM,
        'VPrPripravDo' => PohodaProduct::COL_FLAG_PREPARATION_TO,
        'EAN' => PohodaProduct::COL_POHODA_PRODUCT_EAN,
        'MJ3' => PohodaProduct::COL_POHODA_PRODUCT_UNIT,
        'MJ3Koef' => PohodaProduct::COL_POHODA_PRODUCT_MINIMUM_AMOUNT_AND_MULTIPLIER,
        'Zaruka' => PohodaProduct::COL_POHODA_PRODUCT_WARRANTY,
        'Vyrobce' => PohodaProduct::COL_POHODA_PRODUCT_BRAND_NAME,
    ];

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
        $queryColumns = [];

        foreach (self::POHODA_PRODUCT_COLUMN_ALIASES as $column => $alias) {
            $resultSetMapping->addScalarResult($column, $alias);
            $queryColumns[] = 'Product.' . $column;
        }

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT ' . implode(', ', $queryColumns) . '
             FROM Skz Product
             WHERE Product.ID IN (:pohodaProductIds)
                AND Product.IObchod = 1
             ORDER BY Product.DatSave',
            $resultSetMapping
        )->setParameters([
            'pohodaProductIds' => $pohodaProductIds,
        ]);

        $pohodaProductResult = $query->getResult();
        $pohodaProductsResult = [];
        foreach ($pohodaProductResult as $pohodaProduct) {
            $pohodaProductsResult[(int)$pohodaProduct[PohodaProduct::COL_POHODA_ID]] = $pohodaProduct;
            $pohodaProductsResult[(int)$pohodaProduct[PohodaProduct::COL_POHODA_ID]][PohodaProduct::COL_PRODUCT_CATEGORIES] = [];
            $pohodaProductsResult[(int)$pohodaProduct[PohodaProduct::COL_POHODA_ID]][PohodaProduct::COL_PRODUCT_GROUP_ITEMS] = [];
            $pohodaProductsResult[(int)$pohodaProduct[PohodaProduct::COL_POHODA_ID]][PohodaProduct::COL_RELATED_PRODUCTS] = [];
            $pohodaProductsResult[(int)$pohodaProduct[PohodaProduct::COL_POHODA_ID]][PohodaProduct::COL_PRODUCT_VIDEOS] = [];
        }

        return $pohodaProductsResult;
    }

    /**
     * @param \DateTime|null $lastUpdateTime
     * @return array
     */
    public function findProductPohodaIdsByLastUpdateTime(?DateTime $lastUpdateTime): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('ID', PohodaProduct::COL_POHODA_ID);

        if ($lastUpdateTime !== null) {
            // Timezone in Pohoda is always Europe/Prague and we store dates in UTC so we need to convert the last update time to Pohoda´s timezone
            $lastUpdateTime->setTimezone(new DateTimeZone('Europe/Prague'));
        }

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
                'lastUpdateDateTime' => $lastUpdateTime === null ? PohodaDateTimeHelper::FIRST_UPDATE_TIME : $lastUpdateTime->format(PohodaDateTimeHelper::DATE_TIME_FORMAT),
            ]);

        return $query->getResult();
    }

    /**
     * @param string[] $catnums
     * @return array
     */
    public function getSaleInformationByCatnums(array $catnums): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('ID', PohodaProduct::COL_POHODA_ID)
            ->addScalarResult('IDS', PohodaProduct::COL_CATNUM)
            ->addScalarResult('RefSklad', PohodaProduct::COL_STOCK_ID)
            ->addScalarResult('ProdejDPH', PohodaProduct::COL_SELLING_PRICE);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT 
                Product.ID, 
                Product.IDS, 
                Product.RefSklad,
                Product.ProdejDPH 
             FROM Skz Product
             WHERE Product.IDS IN (:catnums)
                AND Product.IObchod = 1
                AND Product.RefSklad IN (:stocks)
             ORDER BY Product.DatSave',
            $resultSetMapping
        )
            ->setParameters([
                'catnums' => $catnums,
                'stocks' => self::SALE_STOCK_IDS_ORDERED_BY_PRIORITY,
            ]);

        return $query->getResult();
    }

    /**
     * @param string[] $catnums
     * @return array
     */
    public function getStockInformationByCatnums(array $catnums): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('IDS', PohodaProduct::COL_CATNUM)
            ->addScalarResult('RefSklad', PohodaProduct::COL_STOCK_ID)
            ->addScalarResult('VPrExtSklad', PohodaProduct::COL_EXTERNAL_STOCK)
            ->addScalarResult('VPrDispStav', PohodaProduct::COL_STOCK_TOTAL);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT
                Product.IDS,
                Product.RefSklad,
                Product.VPrExtSklad,
                Product.VPrDispStav
            FROM Skz Product
            WHERE Product.IDS IN (:catnums)
                AND Product.IObchod = 1
                AND Product.RefSklad IN (:stocks)
            ORDER BY Product.IDS',
            $resultSetMapping
        )
            ->setParameters([
                'catnums' => $catnums,
                'stocks' => self::PRODUCT_STOCK_IDS,
            ]);

        return $query->getResult();
    }

    /**
     * @param int[] $pohodaProductIds
     * @return array
     */
    public function getProductCategoriesByPohodaIds(array $pohodaProductIds): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('RefAg', PohodaProduct::COL_PRODUCT_REF_ID)
            ->addScalarResult('RefKat', PohodaProduct::COL_CATEGORY_REF_CATEGORY_ID);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT CategoryProduct.RefAg, CategoryProduct.RefKat
            FROM SkRefKat CategoryProduct
            JOIN Skz Product ON Product.ID = CategoryProduct.RefAg
            WHERE CategoryProduct.RefAg IN (:pohodaProductIds)
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
     * @param int[] $pohodaProductIds
     * @return array
     */
    public function getRelatedProductsByPohodaIds(array $pohodaProductIds): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('RefAg', PohodaProduct::COL_PRODUCT_REF_ID)
            ->addScalarResult('RefSKz', PohodaProduct::COL_RELATED_PRODUCT_REF_ID)
            ->addScalarResult('OrderFld', PohodaProduct::COL_RELATED_PRODUCT_POSITION);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT RelatedProducts.RefAg, RelatedProducts.RefSKz, RelatedProducts.OrderFld
            FROM SkzIoZbozi RelatedProducts
            JOIN Skz Product ON Product.ID = RelatedProducts.RefSkz
            WHERE RelatedProducts.RefAg IN (:pohodaProductIds)
                AND Product.IObchod = 1
                AND Product.RefSklad = :defaultStockId
            ORDER BY Product.DatSave',
            $resultSetMapping
        )
            ->setParameters([
                'defaultStockId' => self::DEFAULT_POHODA_STOCK_ID,
                'pohodaProductIds' => $pohodaProductIds,
            ]);

        return $query->getResult();
    }

    /**
     * @param int[] $pohodaProductIds
     * @return array
     */
    public function getProductGroupsByPohodaIds(array $pohodaProductIds): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('RefAg', PohodaProduct::COL_PRODUCT_REF_ID)
            ->addScalarResult('RefSKz', PohodaProduct::COL_PRODUCT_GROUP_ITEM_REF_ID)
            ->addScalarResult('Mnozstvi', PohodaProduct::COL_PRODUCT_GROUP_ITEM_COUNT);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT ProductGroup.RefAg, ProductGroup.RefSKz, ProductGroup.Mnozstvi
            FROM SKzPol AS ProductGroup
            JOIN Skz AS ProductGroupItem ON ProductGroupItem.ID = ProductGroup.RefSKz
            JOIN Skz AS MainProduct ON MainProduct.ID = ProductGroup.RefAg
            WHERE ProductGroup.RefAg IN(:pohodaProductIds)
                AND ProductGroupItem.RefSklad = :defaultStock
                AND ProductGroupItem.IObchod = 1
                AND MainProduct.RelSkTyp = :productTypeGroup
            ORDER BY ProductGroup.OrderFld',
            $resultSetMapping
        )
            ->setParameters([
                'pohodaProductIds' => $pohodaProductIds,
                'defaultStock' => self::DEFAULT_POHODA_STOCK_ID,
                'productTypeGroup' => self::PRODUCT_TYPE_GROUP_ID,
            ]);

        return $query->getResult();
    }

    /**
     * @param int[] $pohodaProductIds
     * @return array
     */
    public function getProductsVideosByPohodaIds(array $pohodaProductIds): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('RefAg', PohodaProduct::COL_PRODUCT_REF_ID)
            ->addScalarResult('URL', PohodaProduct::COL_POHODA_PRODUCT_VIDEO);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT ProductVideos.RefAg, ProductVideos.URL
            FROM SkRefOdkazy ProductVideos
            WHERE ProductVideos.RefAg IN (:pohodaProductIds)',
            $resultSetMapping
        )
            ->setParameters([
                'pohodaProductIds' => $pohodaProductIds,
            ]);

        return $query->getResult();
    }
}

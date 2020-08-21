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

    private const PRODUCT_TYPE_SET_ID = 5;

    private const SELLING_EUR_PRICE_ID = 4;

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
        'VPrBCenaEur' => PohodaProduct::COL_STANDARD_PRICE_EUR,
        'ObjNazev' => PohodaProduct::COL_VARIANT_ID,
        'SText' => PohodaProduct::COL_VARIANT_ALIAS,
        'SText1' => PohodaProduct::COL_VARIANT_ALIAS_SK,
        'RelSkTyp' => PohodaProduct::COL_POHODA_PRODUCT_TYPE,
        'VPrAutomatSK' => PohodaProduct::COL_AUTO_DESCRIPTION_TRANSLATION,
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
        'VPrZobrazCZ' => PohodaProduct::COL_SHOWN,
        'VPrZobrazSK' => PohodaProduct::COL_SHOWN_SK,
        'Objem' => PohodaProduct::COL_VOLUME,
        'VPrVyrOdDod' => PohodaProduct::COL_SUPPLIER_SET,
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

        $resultSetMapping->addScalarResult('ProdejC', PohodaProduct::COL_SELLING_PRICE_EUR);
        $queryColumns[] = 'Prices.ProdejC';
        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT ' . implode(', ', $queryColumns) . '
             FROM Skz Product
             LEFT JOIN SKzCn Prices
                ON Product.ID = Prices.RefAg
                    AND Prices.RefSkCeny = :sellingEurPriceId
             WHERE Product.ID IN (:pohodaProductIds)
                AND Product.IObchod = 1
             ORDER BY Product.DatSave',
            $resultSetMapping
        )->setParameters([
            'pohodaProductIds' => $pohodaProductIds,
            'sellingEurPriceId' => self::SELLING_EUR_PRICE_ID,
        ]);

        $pohodaProductResult = $query->getResult();
        $pohodaProductsResult = [];
        foreach ($pohodaProductResult as $pohodaProduct) {
            $pohodaProductsResult[(int)$pohodaProduct[PohodaProduct::COL_POHODA_ID]] = $pohodaProduct;
            $pohodaProductsResult[(int)$pohodaProduct[PohodaProduct::COL_POHODA_ID]][PohodaProduct::COL_PRODUCT_CATEGORIES] = [];
            $pohodaProductsResult[(int)$pohodaProduct[PohodaProduct::COL_POHODA_ID]][PohodaProduct::COL_PRODUCT_SET_ITEMS] = [];
            $pohodaProductsResult[(int)$pohodaProduct[PohodaProduct::COL_POHODA_ID]][PohodaProduct::COL_RELATED_PRODUCTS] = [];
            $pohodaProductsResult[(int)$pohodaProduct[PohodaProduct::COL_POHODA_ID]][PohodaProduct::COL_PRODUCT_VIDEOS] = [];
            $pohodaProductsResult[(int)$pohodaProduct[PohodaProduct::COL_POHODA_ID]][PohodaProduct::COL_PARAMETERS] = [];
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
    public function getProductSetsByPohodaIds(array $pohodaProductIds): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('RefAg', PohodaProduct::COL_PRODUCT_REF_ID)
            ->addScalarResult('RefSKz', PohodaProduct::COL_PRODUCT_SET_ITEM_REF_ID)
            ->addScalarResult('Mnozstvi', PohodaProduct::COL_PRODUCT_SET_ITEM_COUNT);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT ProductSet.RefAg, ProductSet.RefSKz, ProductSet.Mnozstvi
            FROM SKzPol AS ProductSet
            JOIN Skz AS ProductSetItem ON ProductSetItem.ID = ProductSet.RefSKz
            JOIN Skz AS MainProduct ON MainProduct.ID = ProductSet.RefAg
            WHERE ProductSet.RefAg IN(:pohodaProductIds)
                AND ProductSetItem.RefSklad = :defaultStock
                AND ProductSetItem.IObchod = 1
                AND MainProduct.RelSkTyp = :productTypeSet
            ORDER BY ProductSet.OrderFld',
            $resultSetMapping
        )
            ->setParameters([
                'pohodaProductIds' => $pohodaProductIds,
                'defaultStock' => self::DEFAULT_POHODA_STOCK_ID,
                'productTypeSet' => self::PRODUCT_TYPE_SET_ID,
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

    /**
     * @param int[] $pohodaProductIds
     * @return array
     */
    public function getProductParametersByPohodaIds(array $pohodaProductIds): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('ID', PohodaProduct::COL_PRODUCT_PARAMETER_ID)
            ->addScalarResult('parameterId', PohodaProduct::COL_PARAMETER_ID)
            ->addScalarResult('IDS', PohodaProduct::COL_PARAMETER_NAME)
            ->addScalarResult('RefAg', PohodaProduct::COL_PRODUCT_REF_ID)
            ->addScalarResult('RelTyp', PohodaProduct::COL_PARAMETER_TYPE)
            ->addScalarResult('ValLong', PohodaProduct::COL_PARAMETER_VALUE_TYPE_NUMBER)
            ->addScalarResult('ValText', PohodaProduct::COL_PARAMETER_VALUE_TYPE_TEXT)
            ->addScalarResult('ValList', PohodaProduct::COL_PARAMETER_VALUE_TYPE_LIST);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT ProductParameters.ID, Parameters.ID AS "parameterId", Parameters.IDS, ProductParameters.RefAg, Parameters.RelTyp, ProductParameters.ValText, ProductParameters.ValLong, ParametersList.IDS AS ValList
            FROM SkRefParam ProductParameters
            JOIN SkParam Parameters ON Parameters.ID = ProductParameters.RefParam
            LEFT JOIN SkRefParamList ProductParametersList ON ProductParametersList.RefAg = ProductParameters.RefAg
                AND ProductParametersList.RefParam = ProductParameters.RefParam
                AND ProductParametersList.Sel = 1
            LEFT JOIN SkParamList ParametersList ON ParametersList.ID = ProductParametersList.RefParamList
            WHERE ProductParameters.RefAg IN (:pohodaProductIds)
            ORDER BY ProductParameters.OrderFld, ProductParametersList.OrderFld',
            $resultSetMapping
        )
            ->setParameters([
                'pohodaProductIds' => $pohodaProductIds,
            ]);

        $pohodaParameterResults = $query->getResult();
        $pohodaParameterResult = [];

        foreach ($pohodaParameterResults as $pohodaParameter) {
            $pohodaProductParameterId = (int)$pohodaParameter[PohodaProduct::COL_PRODUCT_PARAMETER_ID];
            $pohodaParameterType = (int)$pohodaParameter[PohodaProduct::COL_PARAMETER_TYPE];

            if ($pohodaParameterType === PohodaParameter::POHODA_PARAMETER_TYPE_LIST_ID && isset($pohodaParameterResult[$pohodaProductParameterId])) {
                $pohodaParameterResult[$pohodaProductParameterId][PohodaProduct::COL_PARAMETER_VALUE_TYPE_LIST] .= ', ' . $pohodaParameter[PohodaProduct::COL_PARAMETER_VALUE_TYPE_LIST];
            } else {
                $pohodaParameterResult[$pohodaProductParameterId] = $pohodaParameter;
            }
        }

        return $pohodaParameterResult;
    }

    /**
     * @param string $mainVariantId
     * @return array
     */
    public function getVariantIdsByMainVariantId(string $mainVariantId): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('ID', PohodaProduct::COL_POHODA_ID);

        $query = $this->pohodaEntityManager->createNativeQuery(
            'SELECT Product.ID
            FROM Skz Product
            WHERE Product.RefSklad = :defaultStockId 
                AND Product.ObjNazev LIKE :variantId
                AND Product.IObchod = 1',
            $resultSetMapping
        )
            ->setParameters([
                'defaultStockId' => self::DEFAULT_POHODA_STOCK_ID,
                'variantId' => $mainVariantId . '*%',
            ]);

        return $query->getResult();
    }
}

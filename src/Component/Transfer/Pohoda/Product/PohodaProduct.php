<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product;

class PohodaProduct
{
    public const COL_POHODA_ID = 'pohodaId';
    public const COL_POHODA_PRODUCT_TYPE = 'pohodaProductType';
    public const COL_NAME = 'name';
    public const COL_NAME_SK = 'nameSk';
    public const COL_CATNUM = 'catnum';
    public const COL_SHORT_DESCRIPTION = 'shortDescription';
    public const COL_LONG_DESCRIPTION = 'longDescription';
    public const COL_REGISTRATION_DISCOUNT_DISABLED = 'registrationDiscountDisabled';
    public const COL_SELLING_PRICE = 'sellingPriceWithVat';
    public const COL_SELLING_VAT_RATE_ID = 'vatRateId';
    public const COL_PURCHASE_PRICE = 'purchasePriceWithVat';
    public const COL_STANDARD_PRICE = 'standardPriceWithVat';
    public const COL_STOCK_ID = 'stockId';
    public const COL_VARIANT_ID = 'variantId';
    public const COL_VARIANT_ALIAS = 'variantAlias';
    public const COL_VARIANT_ALIAS_SK = 'variantAliasSk';
    public const COL_PRODUCT_CATEGORIES = 'productCategories';
    public const COL_PRODUCT_REF_ID = 'productPohodaId';
    public const COL_CATEGORY_REF_CATEGORY_ID = 'categoryPohodaId';
    public const COL_AUTO_EUR_PRICE = 'automaticEurPrice';

    public const COL_EXTERNAL_STOCK = 'externalStock';
    public const COL_STOCK_TOTAL = 'totalStock';

    public const COL_PRODUCT_GROUP_ITEM_REF_ID = 'groupItemPohodaId';
    public const COL_PRODUCT_GROUP_ITEM_COUNT = 'productGroupItemCount';

    public const COL_SALE_INFORMATION = 'saleInformation';
    public const COL_STOCKS_INFORMATION = 'stockInformation';
    public const COL_PRODUCT_GROUP_ITEMS = 'productGroupItems';

    /**
     * @var int
     */
    public $pohodaId;

    /**
     * @var int
     */
    public $pohodaProductType;

    /**
     * @var string
     */
    public $catnum;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string|null
     */
    public $nameSk;

    /**
     * @var string|null
     */
    public $shortDescription;

    /**
     * @var string|null
     */
    public $longDescription;

    /**
     * @var bool
     */
    public $registrationDiscountDisabled;

    /**
     * @var string
     */
    public $sellingPrice;

    /**
     * @var int
     */
    public $vatRateId;

    /**
     * @var string|null
     */
    public $purchasePrice;

    /**
     * @var string|null
     */
    public $standardPrice;

    /**
     * @var array
     */
    public $saleInformation;

    /**
     * @var string|null
     */
    public $variantId;

    /**
     * @var string|null
     */
    public $variantAlias;

    /**
     * @var string|null
     */
    public $variantAliasSk;

    /**
     * @var int[]
     */
    public $pohodaCategoryIds;

    /*
     * @var int[]
     */
    public $stocksInformation;

    /**
     * @var array
     */
    public $productGroups;

    /**
     * @var bool
     */
    public $automaticEurCalculation;

    /**
     * @param array $pohodaProductData
     */
    public function __construct(array $pohodaProductData)
    {
        $this->pohodaId = (int)$pohodaProductData[self::COL_POHODA_ID];
        $this->pohodaProductType = (int)$pohodaProductData[self::COL_POHODA_PRODUCT_TYPE];
        $this->catnum = (string)$pohodaProductData[self::COL_CATNUM];
        $this->name = (string)$pohodaProductData[self::COL_NAME];
        $this->nameSk = (string)$pohodaProductData[self::COL_NAME_SK];
        $this->shortDescription = (string)$pohodaProductData[self::COL_SHORT_DESCRIPTION];
        $this->longDescription = (string)$pohodaProductData[self::COL_LONG_DESCRIPTION];
        $this->registrationDiscountDisabled = (bool)$pohodaProductData[self::COL_REGISTRATION_DISCOUNT_DISABLED];
        $this->sellingPrice = (string)$pohodaProductData[self::COL_SELLING_PRICE];
        $this->vatRateId = (int)$pohodaProductData[self::COL_SELLING_VAT_RATE_ID];
        $this->purchasePrice = $pohodaProductData[self::COL_PURCHASE_PRICE];
        $this->standardPrice = $pohodaProductData[self::COL_STANDARD_PRICE];
        $this->saleInformation = $pohodaProductData[self::COL_SALE_INFORMATION];
        $this->variantId = (string)$pohodaProductData[self::COL_VARIANT_ID];
        $this->variantAlias = (string)$pohodaProductData[self::COL_VARIANT_ALIAS];
        $this->variantAliasSk = (string)$pohodaProductData[self::COL_VARIANT_ALIAS_SK];
        $this->pohodaCategoryIds = $pohodaProductData[self::COL_PRODUCT_CATEGORIES];
        $this->stocksInformation = $pohodaProductData[self::COL_STOCKS_INFORMATION];
        $this->productGroups = $pohodaProductData[self::COL_PRODUCT_GROUP_ITEMS];
        $this->automaticEurCalculation = $pohodaProductData[self::COL_AUTO_EUR_PRICE];
    }
}

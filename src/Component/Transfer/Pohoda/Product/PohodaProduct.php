<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product;

class PohodaProduct
{
    public const COL_POHODA_ID = 'pohodaId';
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
    public const COL_SALE_INFORMATION = 'saleInformation';
    public const COL_VARIANT_ID = 'variantId';
    public const COL_VARIANT_ALIAS = 'variantAlias';
    public const COL_VARIANT_ALIAS_SK = 'variantAliasSk';

    /**
     * @var int
     */
    public $pohodaId;

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
     * @param array $pohodaProductData
     */
    public function __construct(array $pohodaProductData)
    {
        $this->pohodaId = (int)$pohodaProductData[self::COL_POHODA_ID];
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
    }
}

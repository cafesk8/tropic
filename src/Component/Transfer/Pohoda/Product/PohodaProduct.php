<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product;

class PohodaProduct
{
    public const INTERNAL_SUPPLIER_NAME = '2prod';

    public const COL_POHODA_ID = 'pohodaId';
    public const COL_POHODA_PRODUCT_TYPE = 'pohodaProductType';
    public const COL_NAME = 'name';
    public const COL_NAME_SK = 'nameSk';
    public const COL_CATNUM = 'catnum';
    public const COL_SHORT_DESCRIPTION = 'shortDescription';
    public const COL_LONG_DESCRIPTION = 'longDescription';
    public const COL_REGISTRATION_DISCOUNT_DISABLED = 'registrationDiscountDisabled';
    public const COL_PROMO_DISCOUNT_DISABLED = 'promoDiscountDisabled';
    public const COL_REGISTRATION_DISCOUNT_DISABLED_SECOND_DOMAIN = 'registrationDiscountDisabledSecondDomain';
    public const COL_PROMO_DISCOUNT_DISABLED_SECOND_DOMAIN = 'promoDiscountDisabledSecondDomain';
    public const COL_REGISTRATION_DISCOUNT_DISABLED_THIRD_DOMAIN = 'registrationDiscountDisabledThirdDomain';
    public const COL_PROMO_DISCOUNT_DISABLED_THIRD_DOMAIN = 'promoDiscountDisabledThirdDomain';
    public const COL_SELLING_PRICE = 'sellingPriceWithVat';
    public const COL_SELLING_VAT_RATE_ID = 'vatRateId';
    public const COL_PURCHASE_PRICE = 'purchasePriceWithVat';
    public const COL_STANDARD_PRICE = 'standardPriceWithVat';
    public const COL_SELLING_PRICE_EUR = 'sellingEurPriceWithVat';
    public const COL_STANDARD_PRICE_EUR = 'standardEurPriceWithVat';
    public const COL_STOCK_ID = 'stockId';
    public const COL_VARIANT_ID = 'variantId';
    public const COL_VARIANT_ALIAS = 'variantAlias';
    public const COL_VARIANT_ALIAS_SK = 'variantAliasSk';
    public const COL_PRODUCT_CATEGORIES = 'productCategories';
    public const COL_PRODUCT_REF_ID = 'productPohodaId';
    public const COL_CATEGORY_REF_CATEGORY_ID = 'categoryPohodaId';
    public const COL_RELATED_PRODUCT_REF_ID = 'relatedProductPohodaId';
    public const COL_RELATED_PRODUCT_POSITION = 'relatedProductOrder';
    public const COL_DELIVERY_DAYS = 'deliveryDays';
    public const COL_AUTO_DESCRIPTION_TRANSLATION = 'automaticDescriptionTranslation';
    public const COL_FLAG_NEW_FROM = 'flagNewFrom';
    public const COL_FLAG_NEW_TO = 'flagNewTo';
    public const COL_FLAG_CLEARANCE_FROM = 'flagClearanceFrom';
    public const COL_FLAG_CLEARANCE_TO = 'flagClearanceTo';
    public const COL_FLAG_ACTION_FROM = 'flagActionFrom';
    public const COL_FLAG_ACTION_TO = 'flagActionTo';
    public const COL_FLAG_RECOMMENDED_FROM = 'flagRecommendedFrom';
    public const COL_FLAG_RECOMMENDED_TO = 'flagRecommendedTo';
    public const COL_FLAG_DISCOUNT_FROM = 'flagDiscountFrom';
    public const COL_FLAG_DISCOUNT_TO = 'flagDiscountTo';
    public const COL_FLAG_PREPARATION_FROM = 'flagPreparationFrom';
    public const COL_FLAG_PREPARATION_TO = 'flagPreparationTo';
    public const COL_SHOWN = 'shown';
    public const COL_SHOWN_SK = 'shownSk';
    public const COL_SELLING_DENIED = 'sellingDenied';
    public const COL_VOLUME = 'volume';
    public const COL_SUPPLIER_SET = 'supplierSet';
    public const COL_PRIORITY = 'priority';
    public const COL_FOREIGN_SUPPLIER = 'foreignSupplier';
    public const COL_WEIGHT = 'weight';
    public const COL_PRODUCT_SUPPLIER_NAME = 'supplierName';

    public const COL_EXTERNAL_STOCK = 'externalStock';
    public const COL_STOCK_TOTAL = 'totalStock';

    public const COL_PRODUCT_SET_ITEM_REF_ID = 'setItemPohodaId';
    public const COL_PRODUCT_SET_ITEM_COUNT = 'productSetItemCount';

    public const COL_SALE_INFORMATION = 'saleInformation';
    public const COL_STOCKS_INFORMATION = 'stockInformation';
    public const COL_PRODUCT_SET_ITEMS = 'productSetItems';
    public const COL_RELATED_PRODUCTS = 'relatedProducts';
    public const COL_PRODUCT_VIDEOS = 'productVideos';

    public const COL_POHODA_PRODUCT_EAN = 'productEan';
    public const COL_POHODA_PRODUCT_UNIT = 'productUnit';
    public const COL_POHODA_PRODUCT_MINIMUM_AMOUNT_AND_MULTIPLIER = 'minimumAmountAndMultiplier';
    public const COL_POHODA_PRODUCT_WARRANTY = 'productWarranty';
    public const COL_POHODA_PRODUCT_BRAND_NAME = 'brandName';
    public const COL_POHODA_PRODUCT_VIDEO = 'productVideo';

    public const COL_PARAMETERS = 'parameters';

    public const COL_PRODUCT_PARAMETER_ID = 'productParameterID';
    public const COL_PARAMETER_ID = 'parameterID';
    public const COL_PARAMETER_NAME = 'parameterName';
    public const COL_PARAMETER_TYPE = 'parameterType';
    public const COL_PARAMETER_VALUE_TYPE_NUMBER = 'parameterValueNumber';
    public const COL_PARAMETER_VALUE_TYPE_TEXT = 'parameterValueText';
    public const COL_PARAMETER_VALUE_TYPE_LIST = 'parameterValueList';
    public const COL_PARAMETER_VALUE_POSITION = 'productParameterPosition';

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
     * @var bool
     */
    public $promoDiscountDisabled;

    public bool $registrationDiscountDisabledSecondDomain;

    public bool $promoDiscountDisabledSecondDomain;

    public bool $registrationDiscountDisabledThirdDomain;

    public bool $promoDiscountDisabledThirdDomain;

    /**
     * @var string
     */
    public $sellingPrice;

    /**
     * @var int
     */
    public $vatRateId;

    public ?string $purchasePrice;

    /**
     * @var string|null
     */
    public $standardPrice;

    public ?string $sellingPriceEur;

    public ?string $standardPriceEur;

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

    /**
     * @var int[]
     */
    public $stocksInformation;

    /**
     * @var array
     */
    public $productSets;

    /**
     * @var array
     */
    public $relatedProducts;

    /**
     * @var string|null
     */
    public $ean;

    /**
     * @var string
     */
    public $unit;

    /**
     * @var int
     */
    public $minimumAmountAndMultiplier;

    /**
     * @var int|null
     */
    public $warranty;

    /**
     * @var string|null
     */
    public $brandName;

    /**
     * @var array
     */
    public $youtubeVideos;

    /**
     * @var bool
     */
    public $automaticDescriptionTranslation;

    /**
     * @var string|null
     */
    public $deliveryDays;

    /**
     * @var \DateTime|null
     */
    public $flagNewFrom;

    /**
     * @var \DateTime|null
     */
    public $flagNewTo;

    /**
     * @var \DateTime|null
     */
    public $flagClearanceFrom;

    /**
     * @var \DateTime|null
     */
    public $flagClearanceTo;

    /**
     * @var \DateTime|null
     */
    public $flagActionFrom;

    /**
     * @var \DateTime|null
     */
    public $flagActionTo;

    /**
     * @var \DateTime|null
     */
    public $flagRecommendedFrom;

    /**
     * @var \DateTime|null
     */
    public $flagRecommendedTo;

    /**
     * @var \DateTime|null
     */
    public $flagDiscountFrom;

    /**
     * @var \DateTime|null
     */
    public $flagDiscountTo;

    /**
     * @var \DateTime|null
     */
    public $flagPreparationFrom;

    /**
     * @var \DateTime|null
     */
    public $flagPreparationTo;

    /**
     * @var bool
     */
    public $shown;

    /**
     * @var bool
     */
    public $shownSk;

    /**
     * @var \App\Component\Transfer\Pohoda\Product\PohodaParameter[]
     */
    public $parameters;

    /**
     * @var int
     */
    public int $volume;

    public bool $supplierSet;

    public int $priority;

    public bool $foreignSupplier;

    public float $weight;

    public bool $sellingDenied;

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
        $this->promoDiscountDisabled = (bool)$pohodaProductData[self::COL_PROMO_DISCOUNT_DISABLED];
        $this->registrationDiscountDisabledSecondDomain = (bool)$pohodaProductData[self::COL_REGISTRATION_DISCOUNT_DISABLED_SECOND_DOMAIN];
        $this->promoDiscountDisabledSecondDomain = (bool)$pohodaProductData[self::COL_PROMO_DISCOUNT_DISABLED_SECOND_DOMAIN];
        $this->registrationDiscountDisabledThirdDomain = (bool)$pohodaProductData[self::COL_REGISTRATION_DISCOUNT_DISABLED_THIRD_DOMAIN];
        $this->promoDiscountDisabledThirdDomain = (bool)$pohodaProductData[self::COL_PROMO_DISCOUNT_DISABLED_THIRD_DOMAIN];
        $this->sellingPrice = (string)$pohodaProductData[self::COL_SELLING_PRICE];
        $this->vatRateId = (int)$pohodaProductData[self::COL_SELLING_VAT_RATE_ID];
        $this->purchasePrice = $pohodaProductData[self::COL_PURCHASE_PRICE];
        $this->standardPrice = $pohodaProductData[self::COL_STANDARD_PRICE];
        $this->sellingPriceEur = $pohodaProductData[self::COL_SELLING_PRICE_EUR];
        $this->standardPriceEur = $pohodaProductData[self::COL_STANDARD_PRICE_EUR];
        $this->saleInformation = $pohodaProductData[self::COL_SALE_INFORMATION];
        $this->variantId = (string)$pohodaProductData[self::COL_VARIANT_ID];
        $this->variantAlias = (string)$pohodaProductData[self::COL_VARIANT_ALIAS];
        $this->variantAliasSk = (string)$pohodaProductData[self::COL_VARIANT_ALIAS_SK];
        $this->pohodaCategoryIds = $pohodaProductData[self::COL_PRODUCT_CATEGORIES];
        $this->stocksInformation = $pohodaProductData[self::COL_STOCKS_INFORMATION];
        $this->productSets = $pohodaProductData[self::COL_PRODUCT_SET_ITEMS];
        $this->automaticDescriptionTranslation = (bool)$pohodaProductData[self::COL_AUTO_DESCRIPTION_TRANSLATION];
        $this->relatedProducts = $pohodaProductData[self::COL_RELATED_PRODUCTS];
        $this->deliveryDays = $pohodaProductData[self::COL_DELIVERY_DAYS];
        $this->ean = $pohodaProductData[self::COL_POHODA_PRODUCT_EAN];
        $this->unit = $pohodaProductData[self::COL_POHODA_PRODUCT_UNIT];
        $this->minimumAmountAndMultiplier = (int)$pohodaProductData[self::COL_POHODA_PRODUCT_MINIMUM_AMOUNT_AND_MULTIPLIER];
        $this->warranty = (int)$pohodaProductData[self::COL_POHODA_PRODUCT_WARRANTY];
        $this->brandName = $pohodaProductData[self::COL_POHODA_PRODUCT_BRAND_NAME];
        $this->youtubeVideos = $pohodaProductData[self::COL_PRODUCT_VIDEOS];
        $this->flagNewFrom = $pohodaProductData[self::COL_FLAG_NEW_FROM];
        $this->flagNewTo = $pohodaProductData[self::COL_FLAG_NEW_TO];
        $this->flagClearanceFrom = $pohodaProductData[self::COL_FLAG_CLEARANCE_FROM];
        $this->flagClearanceTo = $pohodaProductData[self::COL_FLAG_CLEARANCE_TO];
        $this->flagActionFrom = $pohodaProductData[self::COL_FLAG_ACTION_FROM];
        $this->flagActionTo = $pohodaProductData[self::COL_FLAG_ACTION_TO];
        $this->flagRecommendedFrom = $pohodaProductData[self::COL_FLAG_RECOMMENDED_FROM];
        $this->flagRecommendedTo = $pohodaProductData[self::COL_FLAG_RECOMMENDED_TO];
        $this->flagDiscountFrom = $pohodaProductData[self::COL_FLAG_DISCOUNT_FROM];
        $this->flagDiscountTo = $pohodaProductData[self::COL_FLAG_DISCOUNT_TO];
        $this->flagPreparationFrom = $pohodaProductData[self::COL_FLAG_PREPARATION_FROM];
        $this->flagPreparationTo = $pohodaProductData[self::COL_FLAG_PREPARATION_TO];
        $this->shown = (bool)$pohodaProductData[self::COL_SHOWN];
        $this->shownSk = (bool)$pohodaProductData[self::COL_SHOWN_SK];
        $this->sellingDenied = (bool)$pohodaProductData[self::COL_SELLING_DENIED];
        $this->parameters = $pohodaProductData[self::COL_PARAMETERS];
        $this->volume = (int)$pohodaProductData[self::COL_VOLUME];
        $this->supplierSet = (bool)$pohodaProductData[self::COL_SUPPLIER_SET];
        $this->priority = (int)$pohodaProductData[self::COL_PRIORITY];
        $this->foreignSupplier = (bool)$pohodaProductData[self::COL_FOREIGN_SUPPLIER];
        $this->weight = (float)$pohodaProductData[self::COL_WEIGHT];
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Product\PohodaProduct;
use App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade;
use App\Model\Category\CategoryFacade;
use App\Model\Pricing\Currency\Currency;
use App\Model\Pricing\Currency\CurrencyFacade;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Pricing\Vat\VatFacade;
use App\Model\Product\Availability\AvailabilityFacade;
use App\Model\Product\Brand\Brand;
use App\Model\Product\Brand\BrandFacade;
use App\Model\Product\Flag\Flag;
use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\Flag\ProductFlagDataFactory;
use App\Model\Product\Parameter\Parameter;
use App\Model\Product\Parameter\ParameterDataFactory;
use App\Model\Product\Parameter\ParameterFacade;
use App\Model\Product\Parameter\ParameterValueDataFactory;
use App\Model\Product\Parameter\ProductParameterValueDataFactory;
use App\Model\Product\Product;
use App\Model\Product\ProductData;
use App\Model\Product\ProductFacade;
use App\Model\Product\ProductVariantTropicFacade;
use App\Model\Product\Transfer\Exception\CategoryDoesntExistInEShopException;
use App\Model\Product\Transfer\Exception\DuplicateVariantIdException;
use App\Model\Product\Transfer\Exception\MainVariantNotFoundInEshopException;
use App\Model\Product\Transfer\Exception\ProductNotFoundInEshopException;
use App\Model\Product\Transfer\Exception\RelatedProductNotFoundException;
use App\Model\Product\Unit\Unit;
use App\Model\Product\Unit\UnitFacade;
use App\Model\Store\StoreFacade;
use DateTime;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\FrameworkBundle\Model\Product\Brand\BrandDataFactory;
use Shopsys\FrameworkBundle\Model\Product\Brand\Exception\BrandNotFoundException;
use Shopsys\FrameworkBundle\Model\Product\Unit\Exception\UnitNotFoundException;

class PohodaProductMapper
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Model\Pricing\Vat\VatFacade
     */
    private $vatFacade;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \App\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @var array
     */
    private $storesMapByPohodaId;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \App\Model\Product\Availability\AvailabilityFacade
     */
    private $availabilityFacade;

    /**
     * @var \App\Model\Product\Unit\UnitFacade
     */
    private $unitFacade;

    /**
     * @var \App\Model\Product\Brand\BrandFacade
     */
    private $brandFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Brand\BrandDataFactory
     */
    private $brandDataFactory;

    /**
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    private $logger;

    /**
     * @var \App\Model\Product\Flag\FlagFacade
     */
    private $flagFacade;

    /**
     * @var \App\Model\Product\Flag\ProductFlagDataFactory
     */
    private $productFlagDataFactory;

    /**
     * @var \App\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \App\Model\Product\Parameter\ProductParameterValueDataFactory
     */
    private $productParameterValueDataFactory;

    /**
     * @var \App\Model\Product\Parameter\ParameterDataFactory
     */
    private $parameterDataFactory;

    /**
     * @var \App\Model\Product\Parameter\ParameterValueDataFactory
     */
    private $parameterValueDataFactory;

    /**
     * @var int[]
     */
    private array $productIdsToQueueAgain;

    private PohodaProductExportFacade $pohodaProductExportFacade;

    private ProductVariantTropicFacade $productVariantTropicFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Store\StoreFacade $storeFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     * @param \App\Model\Product\Unit\UnitFacade $unitFacade
     * @param \App\Model\Product\Brand\BrandFacade $brandFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Brand\BrandDataFactory $brandDataFactory
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     * @param \App\Model\Product\Flag\ProductFlagDataFactory $productFlagDataFactory
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \App\Model\Product\Parameter\ProductParameterValueDataFactory $productParameterValueDataFactory
     * @param \App\Model\Product\Parameter\ParameterValueDataFactory $parameterValueDataFactory
     * @param \App\Model\Product\Parameter\ParameterDataFactory $parameterDataFactory
     * @param \App\Model\Product\ProductVariantTropicFacade $productVariantTropicFacade
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade $pohodaProductExportFacade
     */
    public function __construct(
        Domain $domain,
        PricingGroupFacade $pricingGroupFacade,
        VatFacade $vatFacade,
        CategoryFacade $categoryFacade,
        StoreFacade $storeFacade,
        ProductFacade $productFacade,
        CurrencyFacade $currencyFacade,
        AvailabilityFacade $availabilityFacade,
        UnitFacade $unitFacade,
        BrandFacade $brandFacade,
        BrandDataFactory $brandDataFactory,
        TransferLoggerFactory $transferLoggerFactory,
        FlagFacade $flagFacade,
        ProductFlagDataFactory $productFlagDataFactory,
        ParameterFacade $parameterFacade,
        ProductParameterValueDataFactory $productParameterValueDataFactory,
        ParameterValueDataFactory $parameterValueDataFactory,
        ParameterDataFactory $parameterDataFactory,
        ProductVariantTropicFacade $productVariantTropicFacade,
        PohodaProductExportFacade $pohodaProductExportFacade
    ) {
        $this->domain = $domain;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->vatFacade = $vatFacade;
        $this->categoryFacade = $categoryFacade;
        $this->storeFacade = $storeFacade;
        $this->productFacade = $productFacade;
        $this->currencyFacade = $currencyFacade;
        $this->availabilityFacade = $availabilityFacade;
        $this->unitFacade = $unitFacade;
        $this->brandFacade = $brandFacade;
        $this->brandDataFactory = $brandDataFactory;
        $this->flagFacade = $flagFacade;
        $this->productFlagDataFactory = $productFlagDataFactory;
        $this->parameterFacade = $parameterFacade;
        $this->productParameterValueDataFactory = $productParameterValueDataFactory;
        $this->parameterDataFactory = $parameterDataFactory;
        $this->parameterValueDataFactory = $parameterValueDataFactory;
        $this->productVariantTropicFacade = $productVariantTropicFacade;

        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(ProductImportCronModule::TRANSFER_IDENTIFIER);
        $this->productIdsToQueueAgain = [];
        $this->pohodaProductExportFacade = $pohodaProductExportFacade;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @param \App\Model\Product\ProductData $productData
     */
    public function mapPohodaProductToProductData(
        PohodaProduct $pohodaProduct,
        ProductData $productData
    ): void {
        $this->mapBasicInfoToProductData($pohodaProduct, $productData);
        $this->mapVariantToProductData($pohodaProduct, $productData);
        $this->mapFlagsToProductData($pohodaProduct, $productData);
        $this->mapDomainDataToProductData($pohodaProduct, $productData);
        $this->mapRelatedProductsToProductData($pohodaProduct, $productData);
        $this->mapProductParameters($pohodaProduct, $productData);

        $productData->stockQuantityByStoreId = $this->getMappedProductStocks($pohodaProduct->stocksInformation);
        $productData->setItems = $this->getMappedProductSetItems($pohodaProduct->productSets);
        $productData->descriptionAutomaticallyTranslated = $pohodaProduct->automaticDescriptionTranslation;
        $productData->shortDescriptionAutomaticallyTranslated = $pohodaProduct->automaticDescriptionTranslation;

        $this->logger->persistTransferIssues();
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @param \App\Model\Product\ProductData $productData
     */
    private function mapDomainDataToProductData(PohodaProduct $pohodaProduct, ProductData $productData): void
    {
        $categories = [];
        foreach ($pohodaProduct->pohodaCategoryIds as $pohodaCategoryId) {
            $category = $this->categoryFacade->findByPohodaId($pohodaCategoryId);
            if ($category === null) {
                throw new CategoryDoesntExistInEShopException(sprintf(
                    'Category pohodaId=%d doesn´t exist in e-shop database',
                    $pohodaCategoryId
                ));
            }
            $categories[] = $category;
        }

        foreach ($this->domain->getAllIds() as $domainId) {
            $productData->categoriesByDomainId[$domainId] = $categories;
            $this->addPricesForDomain($pohodaProduct, $productData, $domainId);
        }

        $productData->vatsIndexedByDomainId[DomainHelper::CZECH_DOMAIN] = $this->vatFacade->getByPohodaId($pohodaProduct->vatRateId);
        $productData->vatsIndexedByDomainId[DomainHelper::SLOVAK_DOMAIN] = $this->vatFacade->getDefaultVatForDomain(DomainHelper::SLOVAK_DOMAIN);
        $productData->vatsIndexedByDomainId[DomainHelper::ENGLISH_DOMAIN] = $this->vatFacade->getDefaultVatForDomain(DomainHelper::ENGLISH_DOMAIN);
        $productData->shown[DomainHelper::CZECH_DOMAIN] = $pohodaProduct->shown;
        $productData->shown[DomainHelper::SLOVAK_DOMAIN] = $pohodaProduct->shownSk;
        $productData->shown[DomainHelper::ENGLISH_DOMAIN] = $pohodaProduct->shown;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @param \App\Model\Product\ProductData $productData
     */
    private function mapVariantToProductData(PohodaProduct $pohodaProduct, ProductData $productData): void
    {
        $oldVariantId = $productData->variantId;

        if ($this->productVariantTropicFacade->isVariant($pohodaProduct->variantId)
            && $this->productVariantTropicFacade->findMainVariantByVariantId($pohodaProduct->variantId) === null
        ) {
            throw new MainVariantNotFoundInEshopException($pohodaProduct->variantId);
        }

        if ($pohodaProduct->variantId !== null) {
            $existingProductByVariantId = $this->productVariantTropicFacade->findByVariantId($pohodaProduct->variantId);
            if ($existingProductByVariantId !== null
                    && $existingProductByVariantId->getPohodaId() !== $pohodaProduct->pohodaId
            ) {
                throw new DuplicateVariantIdException($pohodaProduct->variantId);
            }
        }

        $productData->variantId = TransformString::emptyToNull($pohodaProduct->variantId);
        $productData->variantAlias[DomainHelper::CZECH_LOCALE] = TransformString::emptyToNull($pohodaProduct->variantAlias);
        $productData->variantAlias[DomainHelper::SLOVAK_LOCALE] = TransformString::emptyToNull($pohodaProduct->variantAliasSk);

        if (!$productData->isNew() &&
            $productData->variantId !== null &&
            $oldVariantId !== $productData->variantId &&
            !str_contains($productData->variantId, ProductVariantTropicFacade::VARIANT_ID_SEPARATOR)
        ) {
            foreach ($this->pohodaProductExportFacade->getVariantIdsByMainVariantId($productData->variantId) as $variantId) {
                $this->productIdsToQueueAgain[] = $variantId;
            }
        }
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @param \App\Model\Product\ProductData $productData
     */
    private function mapBasicInfoToProductData(PohodaProduct $pohodaProduct, ProductData $productData): void
    {
        $productData->pohodaId = $pohodaProduct->pohodaId;
        $productData->pohodaProductType = $pohodaProduct->pohodaProductType;
        $productData->updatedByPohodaAt = new \DateTime();
        $productData->catnum = $pohodaProduct->catnum;
        $productData->name[DomainHelper::CZECH_LOCALE] = TransformString::emptyToNull($pohodaProduct->name);
        $productData->name[DomainHelper::SLOVAK_LOCALE] = TransformString::emptyToNull($pohodaProduct->nameSk);
        $productData->shortDescriptions[DomainHelper::CZECH_DOMAIN] = $pohodaProduct->shortDescription;
        $productData->descriptions[DomainHelper::CZECH_DOMAIN] = $pohodaProduct->longDescription;
        $productData->registrationDiscountDisabled = $pohodaProduct->registrationDiscountDisabled;
        $productData->promoDiscountDisabled = $pohodaProduct->promoDiscountDisabled;
        $productData->deliveryDays = $pohodaProduct->deliveryDays;
        $productData->outOfStockAction = Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY;
        $productData->outOfStockAvailability = $this->availabilityFacade->getDefaultOutOfStockAvailability();
        $productData->usingStock = true;
        $productData->ean = $pohodaProduct->ean;
        $productData->minimumAmount = $pohodaProduct->minimumAmountAndMultiplier;
        $productData->amountMultiplier = $pohodaProduct->minimumAmountAndMultiplier;
        $productData->warranty = $pohodaProduct->warranty;
        $productData->brand = $this->getMappedBrand($pohodaProduct->brandName);
        $productData->unit = $this->getMappedUnit($pohodaProduct);
        $productData->youtubeVideoIds = $this->getMappedYoutubeVideoIds($pohodaProduct->youtubeVideos);
        $productData->orderingPriority = $pohodaProduct->priority;
        $productData->foreignSupplier = $pohodaProduct->foreignSupplier;
        $productData->weight = $pohodaProduct->weight;
        $productData->supplierSet = $pohodaProduct->supplierSet;
        $productData->sellingDenied = $pohodaProduct->sellingDenied;

        switch ($pohodaProduct->volume) {
            case 1:
                $productData->bulky = true;
                $productData->oversized = false;
                break;
            case 2:
                $productData->bulky = false;
                $productData->oversized = true;
                break;
            case 3:
                $productData->bulky = true;
                $productData->oversized = true;
                break;
            default:
                $productData->bulky = false;
                $productData->oversized = false;
        }
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @param \App\Model\Product\ProductData $productData
     */
    private function mapRelatedProductsToProductData(PohodaProduct $pohodaProduct, ProductData $productData): void
    {
        $productData->accessories = [];
        foreach ($pohodaProduct->relatedProducts as $relatedProductArray) {
            $relatedProductPohodaId = (int)$relatedProductArray[PohodaProduct::COL_RELATED_PRODUCT_REF_ID];
            $relatedProduct = $this->productFacade->findByPohodaId($relatedProductPohodaId);
            if ($relatedProduct === null) {
                $this->productIdsToQueueAgain[] = $pohodaProduct->pohodaId;
                throw new RelatedProductNotFoundException(sprintf(
                    'Related product pohodaId=%d doesn´t exist in e-shop database',
                    $relatedProductPohodaId
                ));
            }
            $productData->accessories[$relatedProductArray[PohodaProduct::COL_RELATED_PRODUCT_POSITION]] = $relatedProduct;
        }
    }

    /**
     * @param array $stocksInformation
     * @return array
     */
    private function getMappedProductStocks(array $stocksInformation): array
    {
        $this->loadStoresMapByPohodaId();
        $productStocks = [];
        foreach ($stocksInformation as $pohodaStockId => $stock) {
            if (isset($this->storesMapByPohodaId[$pohodaStockId])) {
                $productStocks[$this->storesMapByPohodaId[$pohodaStockId]] = $stock;
            }
        }

        return $productStocks;
    }

    private function loadStoresMapByPohodaId(): void
    {
        if (empty($this->storesMapByPohodaId)) {
            $this->storesMapByPohodaId = [];
            $stores = $this->storeFacade->getAll();
            foreach ($stores as $store) {
                if ($store->getExternalNumber() !== null) {
                    $this->storesMapByPohodaId[$store->getExternalNumber()] = $store->getId();
                }
            }
        }
    }

    /**
     * @param array $pohodaProductSets
     * @return array
     */
    private function getMappedProductSetItems(array $pohodaProductSets): array
    {
        $productSetItems = [];
        foreach ($pohodaProductSets as $pohodaProductSet) {
            $productSetItemPohodaId = (int)$pohodaProductSet[PohodaProduct::COL_PRODUCT_SET_ITEM_REF_ID];
            $productSetItem = $this->productFacade->findByPohodaId($productSetItemPohodaId);

            if ($productSetItem === null) {
                throw new ProductNotFoundInEshopException(sprintf(
                    'Set item pohodaId=%d not found in e-shop database!',
                    $productSetItemPohodaId
                ));
            }
            $productSetItems[] = [
                'item' => $productSetItem,
                'item_count' => (int)$pohodaProductSet[PohodaProduct::COL_PRODUCT_SET_ITEM_COUNT],
            ];
        }

        return $productSetItems;
    }

    /**
     * Focused to fix values ".0000"
     *
     * @param string $price
     * @return string
     */
    private function fixInvalidPriceFormat(string $price): string
    {
        if (substr($price, 0, 1) === '.') {
            return '0' . $price;
        }

        return $price;
    }

    /**
     * @param string|null $priceString
     * @param string $currencyMultiplier
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    private function getPriceFromString(?string $priceString, string $currencyMultiplier = '1'): ?Money
    {
        if ($priceString === null) {
            return null;
        }

        return Money::create($this->fixInvalidPriceFormat($priceString))->divide($currencyMultiplier, 2);
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @param \App\Model\Product\ProductData $productData
     * @param int $domainId
     */
    private function addPricesForDomain(PohodaProduct $pohodaProduct, ProductData $productData, int $domainId): void
    {
        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId);
        $currencyMultiplier = $currency->getExchangeRate();
        $standardPricingGroupId = $this->pricingGroupFacade->getStandardPricePricingGroup($domainId)->getId();
        $salePricingGroupId = $this->pricingGroupFacade->getSalePricePricingGroup($domainId)->getId();

        $productData->manualInputPricesByPricingGroupId[$this->pricingGroupFacade->getOrdinaryCustomerPricingGroup($domainId)->getId()] = $this->getPriceFromString(
            $currency->getCode() === Currency::CODE_EUR ? $pohodaProduct->sellingPriceEur : $pohodaProduct->sellingPrice
        );
        $productData->manualInputPricesByPricingGroupId[$this->pricingGroupFacade->getPurchasePricePricingGroup($domainId)->getId()] = $this->getPriceFromString(
            $pohodaProduct->purchasePrice,
            $currencyMultiplier
        );

        if ($currency->getCode() === Currency::CODE_EUR && $pohodaProduct->standardPriceEur !== null) {
            $productData->manualInputPricesByPricingGroupId[$standardPricingGroupId] = $this->getPriceFromString($pohodaProduct->standardPriceEur);
        } else {
            $productData->manualInputPricesByPricingGroupId[$standardPricingGroupId] = $this->getPriceFromString($pohodaProduct->standardPrice, $currencyMultiplier);
        }

        $productData->manualInputPricesByPricingGroupId[$salePricingGroupId] = null;

        foreach ($this->storeFacade->getSaleStockExternalNumbersOrderedByPriority() as $stockId) {
            if (isset($pohodaProduct->saleInformation[$stockId])) {
                $productData->manualInputPricesByPricingGroupId[$salePricingGroupId] =
                    $this->getPriceFromString($pohodaProduct->saleInformation[$stockId], $currencyMultiplier);
                break;
            }
        }
    }

    /**
     * @param string|null $pohodaBrandName
     * @return \App\Model\Product\Brand\Brand
     */
    private function getMappedBrand(?string $pohodaBrandName): ?Brand
    {
        if ($pohodaBrandName === null) {
            return null;
        }

        try {
            $brand = $this->brandFacade->getByName($pohodaBrandName);
        } catch (BrandNotFoundException $brandNotFoundException) {
            $brandData = $this->brandDataFactory->create();
            $brandData->name = $pohodaBrandName;
            $brand = $this->brandFacade->create($brandData);
        }

        return $brand;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @return \App\Model\Product\Unit\Unit
     */
    private function getMappedUnit(PohodaProduct $pohodaProduct): Unit
    {
        try {
            $unit = $this->unitFacade->getByPohodaName($pohodaProduct->unit);
        } catch (UnitNotFoundException $exception) {
            $errorMessage = sprintf(
                'U produktu catnum=%s nebyla nalezena v e-shopu jednotka %s, použije se výchozí.',
                $pohodaProduct->catnum,
                $pohodaProduct->unit
            );
            $this->logger->addError($errorMessage, [
                'pohodaUnitName' => $pohodaProduct->unit,
                'productId' => $pohodaProduct->pohodaId,
                'productCatnum' => $pohodaProduct->catnum,
            ]);
            /** @var \App\Model\Product\Unit\Unit $unit */
            $unit = $this->unitFacade->getDefaultUnit();
        }

        return $unit;
    }

    /**
     * @param array $youtubeVideos
     * @return array
     */
    private function getMappedYoutubeVideoIds(array $youtubeVideos): array
    {
        $youtubeVideoIds = [];
        foreach ($youtubeVideos as $youtubeVideo) {
            // https://gist.github.com/ghalusa/6c7f3a00fd2383e5ef33
            if (preg_match(
                '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i',
                $youtubeVideo,
                $youtubeVideoMatch
            )) {
                $youtubeVideoIds[] = $youtubeVideoMatch[1];
            }
        }

        return $youtubeVideoIds;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @param \App\Model\Product\ProductData $productData
     */
    private function mapFlagsToProductData(PohodaProduct $pohodaProduct, ProductData $productData): void
    {
        $flags = $this->flagFacade->getAllIndexedByPohodaId();
        $productData->flags = [];

        if ($pohodaProduct->flagNewFrom !== null || $pohodaProduct->flagNewTo !== null) {
            $productData->flags[] = $this->productFlagDataFactory->create(
                $flags[Flag::POHODA_ID_NEW],
                $this->getDatetimeOrTodayDate($pohodaProduct->flagNewFrom),
                $this->getDatetimeOrNull($pohodaProduct->flagNewTo)
            );
        }

        if ($pohodaProduct->flagClearanceFrom !== null || $pohodaProduct->flagClearanceTo !== null) {
            $productData->flags[] = $this->productFlagDataFactory->create(
                $flags[Flag::POHODA_ID_CLEARANCE],
                $this->getDatetimeOrNull($pohodaProduct->flagClearanceFrom),
                $this->getDatetimeOrNull($pohodaProduct->flagClearanceTo)
            );
        }

        if ($pohodaProduct->flagActionFrom !== null || $pohodaProduct->flagActionTo !== null) {
            $productData->flags[] = $this->productFlagDataFactory->create(
                $flags[Flag::POHODA_ID_ACTION],
                $this->getDatetimeOrNull($pohodaProduct->flagActionFrom),
                $this->getDatetimeOrNull($pohodaProduct->flagActionTo)
            );
        }

        if ($pohodaProduct->flagRecommendedFrom !== null || $pohodaProduct->flagRecommendedTo !== null) {
            $productData->flags[] = $this->productFlagDataFactory->create(
                $flags[Flag::POHODA_ID_RECOMMENDED],
                $this->getDatetimeOrNull($pohodaProduct->flagRecommendedFrom),
                $this->getDatetimeOrNull($pohodaProduct->flagRecommendedTo)
            );
        }

        if ($pohodaProduct->flagDiscountFrom !== null || $pohodaProduct->flagDiscountTo !== null) {
            $productData->flags[] = $this->productFlagDataFactory->create(
                $flags[Flag::POHODA_ID_DISCOUNT],
                $this->getDatetimeOrNull($pohodaProduct->flagDiscountFrom),
                $this->getDatetimeOrNull($pohodaProduct->flagDiscountTo)
            );
        }

        if ($pohodaProduct->flagPreparationFrom !== null || $pohodaProduct->flagPreparationTo !== null) {
            $productData->flags[] = $this->productFlagDataFactory->create(
                $flags[Flag::POHODA_ID_PREPARATION],
                $this->getDatetimeOrNull($pohodaProduct->flagPreparationFrom),
                $this->getDatetimeOrNull($pohodaProduct->flagPreparationTo)
            );
        }
    }

    /**
     * @param string $date
     * @return \DateTime|null
     */
    private function getDatetimeOrNull(?string $date): ?DateTime
    {
        return $date === null ? null : new DateTime($date);
    }

    /**
     * @param string|null $date
     * @return \DateTime
     */
    private function getDatetimeOrTodayDate(?string $date): DateTime
    {
        if($date !== null) {
            new DateTime($date);
        }

        $today = new DateTime();
        return $today->setTime(0,0,0);
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @param \App\Model\Product\ProductData $productData
     */
    private function mapProductParameters(PohodaProduct $pohodaProduct, ProductData $productData): void
    {
        $productData->parameters = [];
        foreach ($pohodaProduct->parameters as $pohodaParameter) {
            $parameter = $this->parameterFacade->findParameterByNames([DomainHelper::CZECH_LOCALE => $pohodaParameter->name]);
            if ($parameter === null) {
                $parameterData = $this->parameterDataFactory->create();
                $parameterData->name[DomainHelper::CZECH_LOCALE] = $pohodaParameter->name;
                $parameterData->type = Parameter::TYPE_DEFAULT;
                $parameterData->visibleOnFrontend = true;
                $parameterData->visible = true;

                $parameter = $this->parameterFacade->create($parameterData);
            }
            foreach ($pohodaParameter->values as $locale => $parameterValue) {
                if ($pohodaParameter->isTypeBool()) {
                    if ((int)$parameterValue === 1) {
                        $parameterValue = t('Ano', [], 'messages', $locale);
                    } else {
                        $parameterValue = t('Ne', [], 'messages', $locale);
                    }
                }
                /** @var \App\Model\Product\Parameter\ProductParameterValueData $productParameterValueData */
                $productParameterValueData = $this->productParameterValueDataFactory->create();
                $parameterValueData = $this->parameterValueDataFactory->create();
                $parameterValueData->text = $parameterValue;
                $parameterValueData->locale = $locale;
                $productParameterValueData->parameterValueData = $parameterValueData;
                $productParameterValueData->parameter = $parameter;
                $productParameterValueData->position = $pohodaParameter->position;
                $productData->parameters[] = $productParameterValueData;
            }
        }
    }

    /**
     * @return int[]
     */
    public function getProductIdsToQueueAgain(): array
    {
        return $this->productIdsToQueueAgain;
    }
}

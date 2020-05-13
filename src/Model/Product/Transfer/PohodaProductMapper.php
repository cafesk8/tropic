<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\Pohoda\Product\PohodaProduct;
use App\Component\Transfer\Pohoda\Product\PohodaProductExportRepository;
use App\Model\Category\CategoryFacade;
use App\Model\Pricing\Currency\Currency;
use App\Model\Pricing\Currency\CurrencyFacade;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Pricing\Vat\VatFacade;
use App\Model\Product\Availability\AvailabilityFacade;
use App\Model\Product\Brand\Brand;
use App\Model\Product\Brand\BrandDataFactory;
use App\Model\Product\Brand\BrandFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductData;
use App\Model\Product\ProductFacade;
use App\Model\Product\Transfer\Exception\CategoryDoesntExistInEShopException;
use App\Model\Product\Transfer\Exception\ProductDoesntExistInEShopException;
use App\Model\Store\StoreFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\FrameworkBundle\Model\Product\Brand\Exception\BrandNotFoundException;
use Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException;
use Shopsys\FrameworkBundle\Model\Product\Unit\Unit;
use Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade;

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
     * @var \Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade
     */
    private $unitFacade;

    /**
     * @var \App\Model\Product\Brand\BrandFacade
     */
    private $brandFacade;

    /**
     * @var \App\Model\Product\Brand\BrandDataFactory
     */
    private $brandDataFactory;

    /**
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    private $logger;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Store\StoreFacade $storeFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade $unitFacade
     * @param \App\Model\Product\Brand\BrandFacade $brandFacade
     * @param \App\Model\Product\Brand\BrandDataFactory $brandDataFactory
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
        BrandDataFactory $brandDataFactory
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
        $this->mapDomainDataToProductData($pohodaProduct, $productData);
        $this->mapRelatedProductsToProductData($pohodaProduct, $productData);
        $productData->stockQuantityByStoreId = $this->getMappedProductStocks($pohodaProduct->stocksInformation);
        $productData->groupItems = $this->getMappedProductGroupItems($pohodaProduct->productGroups);
        $productData->eurCalculatedAutomatically = $pohodaProduct->automaticEurCalculation;
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
                throw new CategoryDoesntExistInEShopException(sprintf('Category pohodaId=%d doesn´t exist in e-shop database', $pohodaCategoryId));
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
        $productData->variantId = TransformString::emptyToNull($pohodaProduct->variantId);
        $productData->variantAlias[DomainHelper::CZECH_LOCALE] = TransformString::emptyToNull($pohodaProduct->variantAlias);
        $productData->variantAlias[DomainHelper::SLOVAK_LOCALE] = TransformString::emptyToNull($pohodaProduct->variantAliasSk);
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
        $productData->deliveryDays = $pohodaProduct->deliveryDays;
        $productData->outOfStockAction = Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY;
        $productData->outOfStockAvailability = $this->availabilityFacade->getDefaultOutOfStockAvailability();
        $productData->usingStock = true;
        $productData->ean = $pohodaProduct->ean;
        $productData->minimumAmount = $pohodaProduct->minimumAmountAndMultiplier;
        $productData->amountMultiplier = $pohodaProduct->minimumAmountAndMultiplier;
        $productData->warranty = $pohodaProduct->warranty;
        $productData->brand = $this->getMapperBrand($pohodaProduct->brandName);
        $productData->unit = $this->getMappedUnit($pohodaProduct->unit);
        $productData->youtubeVideoIds = $this->getMappedYoutubeVideoIds($pohodaProduct->youtubeVideos);
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
                throw new ProductDoesntExistInEShopException(sprintf('Product pohodaId=%d doesn´t exist in e-shop database', $relatedProductPohodaId));
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
     * @param array $pohodaProductGroups
     * @return array
     */
    private function getMappedProductGroupItems(array $pohodaProductGroups): array
    {
        $productGroupItems = [];
        foreach ($pohodaProductGroups as $pohodaProductGroup) {
            $productGroupItemPohodaId = (int)$pohodaProductGroup[PohodaProduct::COL_PRODUCT_GROUP_ITEM_REF_ID];
            $productGroupItem = $this->productFacade->findByPohodaId($productGroupItemPohodaId);

            if ($productGroupItem === null) {
                throw new ProductNotFoundException(sprintf('Group item pohodaId=%d not found!', $productGroupItemPohodaId));
            }
            $productGroupItems[] = [
                'item' => $productGroupItem,
                'item_count' => (int)$pohodaProductGroup[PohodaProduct::COL_PRODUCT_GROUP_ITEM_COUNT],
            ];
        }

        return $productGroupItems;
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
    private function getPriceFromString(?string $priceString, string $currencyMultiplier): ?Money
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
        if ($domainId !== DomainHelper::CZECH_DOMAIN && !$pohodaProduct->automaticEurCalculation) {
            return;
        }

        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId);
        $currencyMultiplier = '1';

        if ($domainId !== DomainHelper::CZECH_DOMAIN && $pohodaProduct->automaticEurCalculation && $currency->getCode() === Currency::CODE_EUR) {
            $currencyMultiplier = $currency->getExchangeRate();
        }

        $productData->manualInputPricesByPricingGroupId[
            $this->pricingGroupFacade->getOrdinaryCustomerPricingGroup($domainId)->getId()
        ] = $this->getPriceFromString($pohodaProduct->sellingPrice, $currencyMultiplier);
        $productData->manualInputPricesByPricingGroupId[
            $this->pricingGroupFacade->getPurchasePricePricingGroup($domainId)->getId()
        ] = $this->getPriceFromString($pohodaProduct->purchasePrice, $currencyMultiplier);
        $productData->manualInputPricesByPricingGroupId[
            $this->pricingGroupFacade->getStandardPricePricingGroup($domainId)->getId()
        ] = $this->getPriceFromString($pohodaProduct->standardPrice, $currencyMultiplier);

        $salePricingGroupId = $this->pricingGroupFacade->getSalePricePricingGroup($domainId)->getId();
        $productData->manualInputPricesByPricingGroupId[$salePricingGroupId] = null;

        foreach (PohodaProductExportRepository::SALE_STOCK_IDS_ORDERED_BY_PRIORITY as $stockId) {
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
            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $youtubeVideo, $youtubeVideoMatch)) {
                $youtubeVideoIds[] = $youtubeVideoMatch[1];
            }
        }

        return $youtubeVideoIds;
    }
}

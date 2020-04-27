<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\Pohoda\Product\PohodaProduct;
use App\Component\Transfer\Pohoda\Product\PohodaProductExportRepository;
use App\Model\Category\CategoryFacade;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Pricing\Vat\VatFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductData;
use App\Model\Product\ProductFacade;
use App\Model\Product\Transfer\Exception\CategoryDoesntExistInEShopException;
use App\Model\Store\StoreFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException;

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
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Store\StoreFacade $storeFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        Domain $domain,
        PricingGroupFacade $pricingGroupFacade,
        VatFacade $vatFacade,
        CategoryFacade $categoryFacade,
        StoreFacade $storeFacade,
        ProductFacade $productFacade
    ) {
        $this->domain = $domain;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->vatFacade = $vatFacade;
        $this->categoryFacade = $categoryFacade;
        $this->storeFacade = $storeFacade;
        $this->productFacade = $productFacade;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @param \App\Model\Product\ProductData $productData
     */
    public function mapPohodaProductToProductData(
        PohodaProduct $pohodaProduct,
        ProductData $productData
    ): void {
        $productData->pohodaId = $pohodaProduct->pohodaId;
        $productData->pohodaProductType = $pohodaProduct->pohodaProductType;
        $productData->updatedByPohodaAt = new \DateTime();
        $productData->catnum = $pohodaProduct->catnum;
        $productData->name[DomainHelper::CZECH_LOCALE] = TransformString::emptyToNull($pohodaProduct->name);
        $productData->name[DomainHelper::SLOVAK_LOCALE] = TransformString::emptyToNull($pohodaProduct->nameSk);
        $productData->shortDescriptions[DomainHelper::CZECH_DOMAIN] = $pohodaProduct->shortDescription;
        $productData->descriptions[DomainHelper::CZECH_DOMAIN] = $pohodaProduct->longDescription;
        $productData->usingStock = true;
        $productData->registrationDiscountDisabled = $pohodaProduct->registrationDiscountDisabled;

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
            $salePricingGroupId = $this->pricingGroupFacade->getSalePricePricingGroup($domainId)->getId();
            $productData->manualInputPricesByPricingGroupId[$this->pricingGroupFacade->getByNameAndDomainId(
                PricingGroup::PRICING_GROUP_ORDINARY_CUSTOMER,
                $domainId
            )->getId()] = Money::create($this->fixInvalidPriceFormat($pohodaProduct->sellingPrice));

            $productData->manualInputPricesByPricingGroupId[
                $this->pricingGroupFacade->getByNameAndDomainId(PricingGroup::PRICING_GROUP_PURCHASE_PRICE, $domainId)->getId()
            ] = $this->getPriceFromString($pohodaProduct->purchasePrice);
            $productData->manualInputPricesByPricingGroupId[
                $this->pricingGroupFacade->getStandardPricePricingGroup($domainId)->getId()
            ] = $this->getPriceFromString($pohodaProduct->standardPrice);

            $productData->manualInputPricesByPricingGroupId[$salePricingGroupId] = null;

            foreach (PohodaProductExportRepository::SALE_STOCK_IDS_ORDERED_BY_PRIORITY as $stockId) {
                if (isset($pohodaProduct->saleInformation[$stockId])) {
                    $productData->manualInputPricesByPricingGroupId[$salePricingGroupId] =
                        $this->getPriceFromString($pohodaProduct->saleInformation[$stockId]);
                    break;
                }
            }
        }

        $productData->vatsIndexedByDomainId[DomainHelper::CZECH_DOMAIN] = $this->vatFacade->getByPohodaId($pohodaProduct->vatRateId);
        $productData->vatsIndexedByDomainId[DomainHelper::SLOVAK_DOMAIN] = $this->vatFacade->getDefaultVatForDomain(DomainHelper::SLOVAK_DOMAIN);
        $productData->vatsIndexedByDomainId[DomainHelper::ENGLISH_DOMAIN] = $this->vatFacade->getDefaultVatForDomain(DomainHelper::ENGLISH_DOMAIN);
        $productData->variantId = TransformString::emptyToNull($pohodaProduct->variantId);
        $productData->variantAlias[DomainHelper::CZECH_LOCALE] = TransformString::emptyToNull($pohodaProduct->variantAlias);
        $productData->variantAlias[DomainHelper::SLOVAK_LOCALE] = TransformString::emptyToNull($pohodaProduct->variantAliasSk);
        $productData->eurCalculatedAutomatically = $pohodaProduct->automaticEurCalculation;
        $productData->stockQuantityByStoreId = $this->getMappedProductStocks($pohodaProduct->stocksInformation);
        /* Please remove this after user story "Zobrazení skladové zásoby u produktu" - this line is temporarily for import */
        $productData->outOfStockAction = Product::OUT_OF_STOCK_ACTION_EXCLUDE_FROM_SALE;
        $productData->groupItems = $this->getMappedProductGroupItems($pohodaProduct->productGroups);
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
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    private function getPriceFromString(?string $priceString): ?Money
    {
        if ($priceString === null) {
            return null;
        }

        return Money::create($this->fixInvalidPriceFormat($priceString));
    }
}

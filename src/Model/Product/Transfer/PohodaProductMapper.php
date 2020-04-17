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
use App\Model\Product\ProductData;
use App\Model\Product\Transfer\Exception\CategoryDoesntExistInEShopException;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\String\TransformString;

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
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     */
    public function __construct(Domain $domain, PricingGroupFacade $pricingGroupFacade, VatFacade $vatFacade, CategoryFacade $categoryFacade)
    {
        $this->domain = $domain;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->vatFacade = $vatFacade;
        $this->categoryFacade = $categoryFacade;
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

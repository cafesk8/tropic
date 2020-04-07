<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\Pohoda\Product\PohodaProduct;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Pricing\Vat\VatFacade;
use App\Model\Product\ProductData;
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
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     */
    public function __construct(Domain $domain, PricingGroupFacade $pricingGroupFacade, VatFacade $vatFacade)
    {
        $this->domain = $domain;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->vatFacade = $vatFacade;
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
        $productData->catnum = $pohodaProduct->catnum;
        $productData->name[DomainHelper::CZECH_LOCALE] = TransformString::emptyToNull($pohodaProduct->name);
        $productData->name[DomainHelper::SLOVAK_LOCALE] = TransformString::emptyToNull($pohodaProduct->nameSk);
        $productData->shortDescriptions[DomainHelper::CZECH_DOMAIN] = $pohodaProduct->shortDescription;
        $productData->descriptions[DomainHelper::CZECH_DOMAIN] = $pohodaProduct->longDescription;
        $productData->usingStock = true;
        $productData->registrationDiscountDisabled = $pohodaProduct->registrationDiscountDisabled;

        foreach ($this->domain->getAllIds() as $domainId) {
            $productData->manualInputPricesByPricingGroupId[$this->pricingGroupFacade->getByNameAndDomainId(
                PricingGroup::PRICING_GROUP_ORDINARY_CUSTOMER,
                $domainId
            )->getId()] = Money::create($this->fixInvalidPriceFormat($pohodaProduct->sellingPrice));

            if ($pohodaProduct->purchasePrice !== null) {
                $productData->manualInputPricesByPricingGroupId[$this->pricingGroupFacade->getByNameAndDomainId(
                    PricingGroup::PRICING_GROUP_PURCHASE_PRICE,
                    $domainId
                )->getId()] = Money::create($this->fixInvalidPriceFormat($pohodaProduct->purchasePrice));
            }

            if ($pohodaProduct->standardPrice !== null) {
                $productData->manualInputPricesByPricingGroupId[
                    $this->pricingGroupFacade->getStandardPricePricingGroup($domainId)->getId()
                ] = Money::create($this->fixInvalidPriceFormat($pohodaProduct->standardPrice));
            }
        }

        $productData->vatsIndexedByDomainId[DomainHelper::CZECH_DOMAIN] = $this->vatFacade->getByPohodaId($pohodaProduct->vatRateId);
        $productData->vatsIndexedByDomainId[DomainHelper::SLOVAK_DOMAIN] = $this->vatFacade->getDefaultVatForDomain(DomainHelper::SLOVAK_DOMAIN);
        $productData->vatsIndexedByDomainId[DomainHelper::ENGLISH_DOMAIN] = $this->vatFacade->getDefaultVatForDomain(DomainHelper::ENGLISH_DOMAIN);
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
}

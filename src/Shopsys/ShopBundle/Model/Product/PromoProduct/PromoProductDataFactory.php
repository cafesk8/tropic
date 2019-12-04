<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\PromoProduct;

class PromoProductDataFactory
{
    /**
     * @return \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductData
     */
    public function create(): PromoProductData
    {
        return new PromoProductData();
    }

    /**
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductData
     */
    public function createForDomainId(int $domainId): PromoProductData
    {
        $promoProductData = $this->create();

        $promoProductData->domainId = $domainId;

        return $promoProductData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProduct $promoProduct
     * @return \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductData
     */
    public function createFromPromoProduct(PromoProduct $promoProduct): PromoProductData
    {
        $promoProductData = $this->create();
        $this->fillFromPromoProduct($promoProductData, $promoProduct);

        return $promoProductData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductData $promoProductData
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProduct $promoProduct
     */
    private function fillFromPromoProduct(PromoProductData $promoProductData, PromoProduct $promoProduct): void
    {
        $promoProductData->domainId = $promoProduct->getDomainId();
        $promoProductData->product = $promoProduct->getProduct();
        $promoProductData->price = $promoProduct->getPrice();
        $promoProductData->minimalCartPrice = $promoProduct->getMinimalCartPrice();
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Product\PromoProduct;

class PromoProductDataFactory
{
    /**
     * @return \App\Model\Product\PromoProduct\PromoProductData
     */
    public function create(): PromoProductData
    {
        return new PromoProductData();
    }

    /**
     * @param int $domainId
     * @return \App\Model\Product\PromoProduct\PromoProductData
     */
    public function createForDomainId(int $domainId): PromoProductData
    {
        $promoProductData = $this->create();

        $promoProductData->domainId = $domainId;

        return $promoProductData;
    }

    /**
     * @param \App\Model\Product\PromoProduct\PromoProduct $promoProduct
     * @return \App\Model\Product\PromoProduct\PromoProductData
     */
    public function createFromPromoProduct(PromoProduct $promoProduct): PromoProductData
    {
        $promoProductData = $this->create();
        $this->fillFromPromoProduct($promoProductData, $promoProduct);

        return $promoProductData;
    }

    /**
     * @param \App\Model\Product\PromoProduct\PromoProductData $promoProductData
     * @param \App\Model\Product\PromoProduct\PromoProduct $promoProduct
     */
    private function fillFromPromoProduct(PromoProductData $promoProductData, PromoProduct $promoProduct): void
    {
        $promoProductData->domainId = $promoProduct->getDomainId();
        $promoProductData->product = $promoProduct->getProduct();
        $promoProductData->price = $promoProduct->getPrice();
        $promoProductData->minimalCartPrice = $promoProduct->getMinimalCartPrice();
        $promoProductData->type = $promoProduct->getType();
    }
}

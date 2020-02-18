<?php

declare(strict_types=1);

namespace App\Model\Product\PromoProduct;

class PromoProductFactory
{
    /**
     * @param \App\Model\Product\PromoProduct\PromoProductData $promoProductData
     * @return \App\Model\Product\PromoProduct\PromoProduct
     */
    public function create(PromoProductData $promoProductData): PromoProduct
    {
        return new PromoProduct($promoProductData);
    }
}

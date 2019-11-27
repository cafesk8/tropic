<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\PromoProduct;

class PromoProductFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductData $promoProductData
     * @return \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProduct
     */
    public function create(PromoProductData $promoProductData): PromoProduct
    {
        return new PromoProduct($promoProductData);
    }
}

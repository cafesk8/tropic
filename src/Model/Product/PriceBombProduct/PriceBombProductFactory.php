<?php

declare(strict_types=1);

namespace App\Model\Product\PriceBombProduct;

use Shopsys\FrameworkBundle\Model\Product\Product;

class PriceBombProductFactory
{
    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param int $position
     * @return \App\Model\Product\PriceBombProduct\PriceBombProduct
     */
    public function create(
        Product $product,
        int $domainId,
        int $position
    ): PriceBombProduct {
        return new PriceBombProduct($product, $domainId, $position);
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Product\Set;

use App\Model\Product\Product;

class ProductSetFactory
{
    /**
     * @param \App\Model\Product\Product $mainProduct
     * @param \App\Model\Product\Product $item
     * @param int $itemCount
     * @return \App\Model\Product\Set\ProductSet
     */
    public function create(
        Product $mainProduct,
        Product $item,
        int $itemCount
    ): ProductSet {
        return new ProductSet($mainProduct, $item, $itemCount);
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Product\Group;

use App\Model\Product\Product;

class ProductGroupFactory
{
    /**
     * @param \App\Model\Product\Product $mainProduct
     * @param \App\Model\Product\Product $item
     * @param int $itemCount
     * @return \App\Model\Product\Group\ProductGroup
     */
    public function create(
        Product $mainProduct,
        Product $item,
        int $itemCount
    ): ProductGroup {
        return new ProductGroup($mainProduct, $item, $itemCount);
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use App\Model\Product\Product;

class ProductFlagFactory
{
    /**
     * @param \App\Model\Product\Flag\ProductFlagData $productFlagData
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Flag\ProductFlag
     */
    public function create(ProductFlagData $productFlagData, Product $product): ProductFlag
    {
        return ProductFlag::create($productFlagData, $product);
    }
}

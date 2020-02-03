<?php

declare(strict_types=1);

namespace App\Model\Product\ProductGift;

class ProductGiftFactory
{
    /**
     * @param \App\Model\Product\ProductGift\ProductGiftData $productGiftData
     * @return \App\Model\Product\ProductGift\ProductGift
     */
    public function create(ProductGiftData $productGiftData): ProductGift
    {
        return new ProductGift($productGiftData);
    }
}

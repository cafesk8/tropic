<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\ProductGift;

class ProductGiftFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftData $productGiftData
     * @return \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGift
     */
    public function create(ProductGiftData $productGiftData): ProductGift
    {
        return new ProductGift($productGiftData);
    }
}

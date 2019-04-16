<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\StoreStock;

use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Store\Store;

class ProductStoreStockFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Store\Store $store
     * @param int|null $stockQuantity
     * @return \Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStock
     */
    public function create(
        Product $product,
        Store $store,
        ?int $stockQuantity
    ): ProductStoreStock {
        return ProductStoreStock::create($product, $store, $stockQuantity);
    }
}

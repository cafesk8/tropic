<?php

declare(strict_types=1);

namespace App\Model\Product\StoreStock;

use App\Model\Product\Product;
use App\Model\Store\Store;

class ProductStoreStockFactory
{
    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Store\Store $store
     * @param int|null $stockQuantity
     * @return \App\Model\Product\StoreStock\ProductStoreStock
     */
    public function create(
        Product $product,
        Store $store,
        ?int $stockQuantity
    ): ProductStoreStock {
        return ProductStoreStock::create($product, $store, $stockQuantity);
    }
}

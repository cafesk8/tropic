<?php

declare(strict_types=1);

namespace App\Model\Product\Bestseller;

use App\Model\Product\Product;

class BestsellerFactory
{
    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param int $position
     * @return \App\Model\Product\Bestseller\Bestseller
     */
    public function create(Product $product, int $domainId, int $position): Bestseller
    {
        return new Bestseller($product, $domainId, $position);
    }
}
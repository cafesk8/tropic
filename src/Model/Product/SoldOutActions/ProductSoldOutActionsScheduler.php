<?php

declare(strict_types=1);

namespace App\Model\Product\SoldOutActions;

use App\Model\Product\Product;

class ProductSoldOutActionsScheduler
{
    /**
     * @var \App\Model\Product\Product[]
     */
    private array $productsToProcess = [];

    /**
     * @param \App\Model\Product\Product $product
     */
    public function scheduleProduct(Product $product): void
    {
        $this->productsToProcess[$product->getId()] = $product;
    }

    /**
     * @return \App\Model\Product\Product[]
     */
    public function getProductsToProcessAndClean(): array
    {
        $products = $this->productsToProcess;
        $this->productsToProcess = [];

        return $products;
    }
}

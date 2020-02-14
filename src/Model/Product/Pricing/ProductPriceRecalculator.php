<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculator as BaseProductPriceRecalculator;
use Shopsys\FrameworkBundle\Model\Product\Product;

class ProductPriceRecalculator extends BaseProductPriceRecalculator
{
    /**
     * @param \App\Model\Product\Product $product
     */
    public function recalculateOneProductPrices(Product $product): void
    {
        $this->recalculateProductPrices($product);
    }

    public function refreshAllPricingGroups(): void
    {
        $this->allPricingGroups = $this->pricingGroupFacade->getAll();
    }
}

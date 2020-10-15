<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculator as BaseProductAvailabilityRecalculator;
use Shopsys\FrameworkBundle\Model\Product\Product;

/**
 * @property \App\Component\EntityExtension\EntityManagerDecorator $em
 */
class ProductAvailabilityRecalculator extends BaseProductAvailabilityRecalculator
{
    /**
     * In contrast to the framework, we do not need to recalculate main variant availability at all
     *
     * @param \App\Model\Product\Product $product
     */
    protected function recalculateProductAvailability(Product $product)
    {
        $calculatedAvailability = $this->productAvailabilityCalculation->calculateAvailability($product);
        $product->setCalculatedAvailability($calculatedAvailability);
        $this->em->flush($product);
    }
}

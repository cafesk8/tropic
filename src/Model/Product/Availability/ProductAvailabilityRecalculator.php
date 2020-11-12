<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculator as BaseProductAvailabilityRecalculator;

/**
 * @property \App\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Model\Product\Availability\ProductAvailabilityCalculation $productAvailabilityCalculation
 */
class ProductAvailabilityRecalculator extends BaseProductAvailabilityRecalculator
{
    /**
     * @param \App\Model\Product\Product $product
     */
    public function recalculateOneProductAvailability(Product $product): void
    {
        $this->recalculateProductAvailability($product);
    }

    /**
     * We need to "refresh" (using EntityManager::find) product because it might not be in the identity map.
     * This happens in products import from Pohoda
     */
    public function runImmediateRecalculations()
    {
        $products = $this->productAvailabilityRecalculationScheduler->getProductsForImmediateRecalculation();
        foreach ($products as $product) {
            $product = $this->em->find(Product::class, $product->getId());
            $this->recalculateProductAvailability($product);
        }
        $this->productAvailabilityRecalculationScheduler->cleanScheduleForImmediateRecalculation();
    }
}

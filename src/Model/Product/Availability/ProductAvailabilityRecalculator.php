<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculator as BaseProductAvailabilityRecalculator;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;

/**
 * @property \App\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Model\Product\Availability\ProductAvailabilityCalculation $productAvailabilityCalculation
 */
class ProductAvailabilityRecalculator extends BaseProductAvailabilityRecalculator
{
    /**
     * In contrast to the framework, we do not need to recalculate main variant availability at all
     *
     * @param \App\Model\Product\Product $product
     */
    protected function recalculateProductAvailability(BaseProduct $product)
    {
        $calculatedAvailability = $this->productAvailabilityCalculation->calculateAvailability($product);
        $product->setCalculatedAvailability($calculatedAvailability);
        $this->em->flush($product);
    }

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

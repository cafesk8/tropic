<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityCalculation as BaseProductAvailabilityCalculation;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;

/**
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method __construct(\Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade $availabilityFacade, \App\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator, \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade, \Doctrine\ORM\EntityManagerInterface $em, \App\Model\Product\ProductRepository $productRepository)
 * @method \App\Model\Product\Availability\Availability calculateMainVariantAvailability(\App\Model\Product\Product $mainVariant)
 * @method \App\Model\Product\Product[] getAtLeastSomewhereSellableVariantsByMainVariant(\App\Model\Product\Product $mainVariant)
 */
class ProductAvailabilityCalculation extends BaseProductAvailabilityCalculation
{
    /**
     * @param \App\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator
     */
    protected $productSellingDeniedRecalculator;

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Availability\Availability|null
     */
    public function calculateAvailability(BaseProduct $product)
    {
        /** @var \App\Model\Product\Availability\Availability $defaultInStockAvailability */
        $defaultInStockAvailability = $this->availabilityFacade->getDefaultInStockAvailability();

        if ($this->em->contains($product) === false) {
            $product->markForAvailabilityRecalculation();

            return $defaultInStockAvailability;
        }

        if ($product->isMainVariant()) {
            return $this->calculateMainVariantAvailability($product);
        }
        if ($product->isUsingStock()) {
            if ($product->getCalculatedSellingDenied() ||
                ($product->getRealStockQuantity() <= 0 && $product->getOutOfStockAction() === BaseProduct::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY)
            ) {
                return $product->getOutOfStockAvailability();
            } else {
                return $defaultInStockAvailability;
            }
        } else {
            return $product->getAvailability();
        }
    }
}

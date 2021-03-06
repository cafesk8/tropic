<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityCalculation as BaseProductAvailabilityCalculation;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;

/**
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method __construct(\App\Model\Product\Availability\AvailabilityFacade $availabilityFacade, \App\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator, \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade, \Doctrine\ORM\EntityManagerInterface $em, \App\Model\Product\ProductRepository $productRepository)
 * @method \App\Model\Product\Availability\Availability calculateMainVariantAvailability(\App\Model\Product\Product $mainVariant)
 * @method \App\Model\Product\Product[] getAtLeastSomewhereSellableVariantsByMainVariant(\App\Model\Product\Product $mainVariant)
 * @property \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
 * @method \App\Model\Product\Availability\Availability calculateAvailabilityForUsingStockProduct(\App\Model\Product\Product $product)
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
        $defaultInStockAvailability = $this->availabilityFacade->getDefaultInStockAvailability();

        if ($this->em->contains($product) === false) {
            $product->markForAvailabilityRecalculation();

            return $defaultInStockAvailability;
        }

        return $this->availabilityFacade->getAvailability($product);
    }
}

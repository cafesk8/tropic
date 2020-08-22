<?php

declare(strict_types=1);

namespace App\Model\Product\Accessory;

use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade as BaseProductAccessoryFacade;

/**
 * @property \App\Model\Product\Accessory\ProductAccessoryRepository $productAccessoryRepository
 * @method __construct(\App\Model\Product\Accessory\ProductAccessoryRepository $productAccessoryRepository)
 * @method \App\Model\Product\Product[] getTopOfferedAccessories(\App\Model\Product\Product $product, int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, int $limit)
 */
class ProductAccessoryFacade extends BaseProductAccessoryFacade
{
    /**
     * @param int $productId
     * @return int[]
     */
    public function getProductIds(int $productId): array
    {
        return $this->productAccessoryRepository->getProductIds($productId);
    }
}

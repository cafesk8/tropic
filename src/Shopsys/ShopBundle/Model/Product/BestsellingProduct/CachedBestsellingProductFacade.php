<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\BestsellingProduct;

use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\BestsellingProduct\CachedBestsellingProductFacade as BaseCachedBestsellingProductFacade;

class CachedBestsellingProductFacade extends BaseCachedBestsellingProductFacade
{
    /**
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Category\Category $category
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return int[]
     */
    public function getAllOfferedBestsellingProductIds($domainId, Category $category, PricingGroup $pricingGroup): array
    {
        $cacheId = $this->getCacheId($domainId, $category, $pricingGroup);
        $sortedProductsIds = $this->cacheProvider->fetch($cacheId);

        if ($sortedProductsIds === false) {
            $bestsellingProducts = $this->bestsellingProductFacade->getAllOfferedBestsellingProducts(
                $domainId,
                $category,
                $pricingGroup
            );
            $this->saveToCache($bestsellingProducts, $cacheId);
            $sortedProductsIds = $this->cacheProvider->fetch($cacheId);
        }

        return $sortedProductsIds;
    }
}

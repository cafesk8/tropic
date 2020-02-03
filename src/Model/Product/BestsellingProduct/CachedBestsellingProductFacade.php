<?php

declare(strict_types=1);

namespace App\Model\Product\BestsellingProduct;

use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\BestsellingProduct\CachedBestsellingProductFacade as BaseCachedBestsellingProductFacade;

/**
 * @property \App\Model\Product\ProductRepository $productRepository
 * @property \App\Model\Pricing\Group\PricingGroupRepository $pricingGroupRepository
 * @method __construct(\Doctrine\Common\Cache\CacheProvider $cacheProvider, \Shopsys\FrameworkBundle\Model\Product\BestsellingProduct\BestsellingProductFacade $bestsellingProductFacade, \App\Model\Product\ProductRepository $productRepository, \App\Model\Pricing\Group\PricingGroupRepository $pricingGroupRepository)
 * @method \App\Model\Product\Product[] getAllOfferedBestsellingProducts(int $domainId, \App\Model\Category\Category $category, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method invalidateCacheByDomainIdAndCategory(int $domainId, \App\Model\Category\Category $category)
 * @method saveToCache(\App\Model\Product\Product[] $bestsellingProducts, string $cacheId)
 * @method \App\Model\Product\Product[] getSortedProducts(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, int[] $sortedProductIds)
 * @method string getCacheId(int $domainId, \App\Model\Category\Category $category, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class CachedBestsellingProductFacade extends BaseCachedBestsellingProductFacade
{
    /**
     * @param int $domainId
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
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

<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\BestsellingProduct;

use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\BestsellingProduct\CachedBestsellingProductFacade as BaseCachedBestsellingProductFacade;

/**
 * @property \Shopsys\ShopBundle\Model\Product\ProductRepository $productRepository
 * @property \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupRepository $pricingGroupRepository
 * @method __construct(\Doctrine\Common\Cache\CacheProvider $cacheProvider, \Shopsys\FrameworkBundle\Model\Product\BestsellingProduct\BestsellingProductFacade $bestsellingProductFacade, \Shopsys\ShopBundle\Model\Product\ProductRepository $productRepository, \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupRepository $pricingGroupRepository)
 * @method \Shopsys\ShopBundle\Model\Product\Product[] getAllOfferedBestsellingProducts(int $domainId, \Shopsys\ShopBundle\Model\Category\Category $category, \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method invalidateCacheByDomainIdAndCategory(int $domainId, \Shopsys\ShopBundle\Model\Category\Category $category)
 * @method saveToCache(\Shopsys\ShopBundle\Model\Product\Product[] $bestsellingProducts, string $cacheId)
 * @method \Shopsys\ShopBundle\Model\Product\Product[] getSortedProducts(int $domainId, \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup, int[] $sortedProductIds)
 * @method string getCacheId(int $domainId, \Shopsys\ShopBundle\Model\Category\Category $category, \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class CachedBestsellingProductFacade extends BaseCachedBestsellingProductFacade
{
    /**
     * @param int $domainId
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
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

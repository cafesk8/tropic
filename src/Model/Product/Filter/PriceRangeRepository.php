<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Product\Filter\PriceRangeRepository as BasePriceRangeRepository;

/**
 * @deprecated
 * @see \App\Model\Product\Filter\Elasticsearch\ProductFilterConfigFactory
 *
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method __construct(\App\Model\Product\ProductRepository $productRepository, \Shopsys\FrameworkBundle\Component\Doctrine\QueryBuilderExtender $queryBuilderExtender)
 * @method \Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange getPriceRangeInCategory(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, \App\Model\Category\Category $category)
 * @method \Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange getPriceRangeForSearch(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, string $locale, string|null $searchText)
 * @method \Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange getPriceRangeByProductsQueryBuilder(\Doctrine\ORM\QueryBuilder $productsQueryBuilder, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class PriceRangeRepository extends BasePriceRangeRepository
{
}

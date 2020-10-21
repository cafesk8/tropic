<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Product\Filter\BrandFilterChoiceRepository as BaseBrandFilterChoiceRepository;

/**
 * @deprecated
 * @see \App\Model\Product\Filter\Elasticsearch\ProductFilterConfigFactory
 *
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method __construct(\App\Model\Product\ProductRepository $productRepository)
 * @method \App\Model\Product\Brand\Brand[] getBrandFilterChoicesForSearch(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, string $locale, string|null $searchText)
 * @method \App\Model\Product\Brand\Brand[] getBrandsByProductsQueryBuilder(\Doctrine\ORM\QueryBuilder $productsQueryBuilder)
 * @method \App\Model\Product\Brand\Brand[] getBrandFilterChoicesInCategory(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, \App\Model\Category\Category $category)
 */
class BrandFilterChoiceRepository extends BaseBrandFilterChoiceRepository
{
}

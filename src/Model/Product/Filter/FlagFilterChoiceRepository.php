<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Product\Filter\FlagFilterChoiceRepository as BaseFlagFilterChoiceRepository;

/**
 * @deprecated
 * @see \App\Model\Product\Filter\Elasticsearch\ProductFilterConfigFactory
 *
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method \App\Model\Product\Flag\Flag[] getFlagFilterChoicesInCategory(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, string $locale, \App\Model\Category\Category $category)
 * @method \App\Model\Product\Flag\Flag[] getFlagFilterChoicesForSearch(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, string $locale, string|null $searchText)
 * @method __construct(\App\Model\Product\ProductRepository $productRepository)
 * @method \App\Model\Product\Flag\Flag[] getVisibleFlagsByProductsQueryBuilder(\Doctrine\ORM\QueryBuilder $productsQueryBuilder, string $locale)
 */
class FlagFilterChoiceRepository extends BaseFlagFilterChoiceRepository
{
}

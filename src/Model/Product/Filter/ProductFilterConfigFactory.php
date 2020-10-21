<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfigFactory as BaseProductFilterConfigFactory;

/**
 * @deprecated
 * @see \App\Model\Product\Filter\Elasticsearch\ProductFilterConfigFactory
 * 
 * @property \App\Model\Product\Filter\ParameterFilterChoiceRepository $parameterFilterChoiceRepository
 * @property \App\Model\Product\Filter\PriceRangeRepository $priceRangeRepository
 * @method __construct(\App\Model\Product\Filter\ParameterFilterChoiceRepository $parameterFilterChoiceRepository, \App\Model\Product\Filter\FlagFilterChoiceRepository $flagFilterChoiceRepository, \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser, \App\Model\Product\Filter\BrandFilterChoiceRepository $brandFilterChoiceRepository, \App\Model\Product\Filter\PriceRangeRepository $priceRangeRepository)
 * @property \App\Model\Product\Filter\FlagFilterChoiceRepository $flagFilterChoiceRepository
 * @property \App\Model\Product\Filter\BrandFilterChoiceRepository $brandFilterChoiceRepository
 * @method \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig createForCategory(int $domainId, string $locale, \App\Model\Category\Category $category)
 */
class ProductFilterConfigFactory extends BaseProductFilterConfigFactory
{
}

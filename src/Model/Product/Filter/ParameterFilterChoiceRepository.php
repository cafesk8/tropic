<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoiceRepository as BaseParameterFilterChoiceRepository;

/**
 * @deprecated
 * @see \App\Model\Product\Filter\Elasticsearch\ProductFilterConfigFactory
 *
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Product\ProductRepository $productRepository)
 * @method \App\Model\Product\Parameter\Parameter[] getVisibleParametersIndexedByIdOrderedByName(array $rows, string $locale)
 * @method \App\Model\Product\Parameter\ParameterValue[][] getParameterValuesIndexedByParameterIdOrderedByValueText(array $rows, string $locale)
 * @method \Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoice[] getParameterFilterChoicesInCategory(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, string $locale, \App\Model\Category\Category $category)
 * @method \App\Model\Product\Parameter\ParameterValue[] getParameterValuesIndexedByIdOrderedByText(array $rows, string $locale)
 */
class ParameterFilterChoiceRepository extends BaseParameterFilterChoiceRepository
{
}

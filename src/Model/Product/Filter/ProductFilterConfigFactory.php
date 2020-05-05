<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Category\Category as BaseCategory;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfigFactory as BaseProductFilterConfigFactory;

/**
 * @property \App\Model\Product\Filter\ParameterFilterChoiceRepository $parameterFilterChoiceRepository
 * @property \App\Model\Product\Filter\PriceRangeRepository $priceRangeRepository
 * @method __construct(\App\Model\Product\Filter\ParameterFilterChoiceRepository $parameterFilterChoiceRepository, \Shopsys\FrameworkBundle\Model\Product\Filter\FlagFilterChoiceRepository $flagFilterChoiceRepository, \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser, \Shopsys\FrameworkBundle\Model\Product\Filter\BrandFilterChoiceRepository $brandFilterChoiceRepository, \App\Model\Product\Filter\PriceRangeRepository $priceRangeRepository)
 */
class ProductFilterConfigFactory extends BaseProductFilterConfigFactory
{
    /**
     * @param int $domainId
     * @param string $locale
     * @param \App\Model\Category\Category $category
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig
     */
    public function createForCategory($domainId, $locale, BaseCategory $category)
    {
        $productFilterConfig = parent::createForCategory($domainId, $locale, $category);

        if ($category->isSaleType()) {
            $productFilterConfig = new ProductFilterConfig(
                [],
                $productFilterConfig->getFlagChoices(),
                $productFilterConfig->getBrandChoices(),
                $productFilterConfig->getPriceRange()
            );
        }

        return $productFilterConfig;
    }
}

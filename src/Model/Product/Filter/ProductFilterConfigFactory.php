<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Category\Category as BaseCategory;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfigFactory as BaseProductFilterConfigFactory;

/**
 * @property \App\Model\Product\Filter\ParameterFilterChoiceRepository $parameterFilterChoiceRepository
 * @property \App\Model\Product\Filter\PriceRangeRepository $priceRangeRepository
 * @method __construct(\App\Model\Product\Filter\ParameterFilterChoiceRepository $parameterFilterChoiceRepository, \App\Model\Product\Filter\FlagFilterChoiceRepository $flagFilterChoiceRepository, \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser, \App\Model\Product\Filter\BrandFilterChoiceRepository $brandFilterChoiceRepository, \App\Model\Product\Filter\PriceRangeRepository $priceRangeRepository)
 * @property \App\Model\Product\Filter\FlagFilterChoiceRepository $flagFilterChoiceRepository
 * @property \App\Model\Product\Filter\BrandFilterChoiceRepository $brandFilterChoiceRepository
 */
class ProductFilterConfigFactory extends BaseProductFilterConfigFactory
{
    /**
     * @param int $domainId
     * @param string $locale
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Product\Flag\Flag[] $onlyFlags
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig
     */
    public function createForCategory($domainId, $locale, BaseCategory $category, array $onlyFlags = [])
    {
        if ($onlyFlags) {
            $pricingGroup = $this->currentCustomerUser->getPricingGroup();

            $productFilterConfig = new ProductFilterConfig(
                [],
                [],
                $this->brandFilterChoiceRepository->getBrandFilterChoicesInCategory($domainId, $pricingGroup, $category, $onlyFlags),
                $this->priceRangeRepository->getPriceRangeInCategory($domainId, $pricingGroup, $category, $onlyFlags)
            );
        } else {
            $productFilterConfig = parent::createForCategory($domainId, $locale, $category);

            if ($category->isSaleType() || $category->isNewsType()) {
                $productFilterConfig = new ProductFilterConfig(
                    [],
                    [],
                    $productFilterConfig->getBrandChoices(),
                    $productFilterConfig->getPriceRange()
                );
            }
        }

        return $productFilterConfig;
    }
}

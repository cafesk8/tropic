<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig as BaseProductFilterConfig;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfigFactory as BaseProductFilterConfigFactory;

/**
 * @property \App\Model\Product\Filter\ParameterFilterChoiceRepository $parameterFilterChoiceRepository
 * @method __construct(\App\Model\Product\Filter\ParameterFilterChoiceRepository $parameterFilterChoiceRepository, \Shopsys\FrameworkBundle\Model\Product\Filter\FlagFilterChoiceRepository $flagFilterChoiceRepository, \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser, \Shopsys\FrameworkBundle\Model\Product\Filter\BrandFilterChoiceRepository $brandFilterChoiceRepository, \Shopsys\FrameworkBundle\Model\Product\Filter\PriceRangeRepository $priceRangeRepository)
 */
class ProductFilterConfigFactory extends BaseProductFilterConfigFactory
{
    /**
     * @param int $domainId
     * @param string $locale
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Product\Filter\ProductFilterConfig
     */
    public function createForCategory($domainId, $locale, Category $category): ProductFilterConfig
    {
        $pricingGroup = $this->currentCustomerUser->getPricingGroup();
        $parent = parent::createForCategory($domainId, $locale, $category);
        $colorChoices = $this->parameterFilterChoiceRepository->getColorParameterFilterChoicesInCategory(
            $domainId,
            $pricingGroup,
            $locale,
            $category
        );
        $sizeChoices = $this->parameterFilterChoiceRepository->getSizeParameterFilterChoicesInCategory(
            $domainId,
            $pricingGroup,
            $locale,
            $category
        );

        return $this->createFromParent($parent, $colorChoices, $sizeChoices);
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @param string|null $searchText
     * @return \App\Model\Product\Filter\ProductFilterConfig
     */
    public function createForSearch($domainId, $locale, $searchText): ProductFilterConfig
    {
        $parent = parent::createForSearch($domainId, $locale, $searchText);

        return $this->createFromParent($parent, [], []);
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterConfig $parent
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoice[] $colorChoices
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoice[] $sizeChoices
     * @return \App\Model\Product\Filter\ProductFilterConfig
     */
    private function createFromParent(BaseProductFilterConfig $parent, array $colorChoices, array $sizeChoices): ProductFilterConfig
    {
        return new ProductFilterConfig(
            $parent->getParameterChoices(),
            $parent->getFlagChoices(),
            $parent->getBrandChoices(),
            $parent->getPriceRange(),
            $colorChoices,
            $sizeChoices
        );
    }
}

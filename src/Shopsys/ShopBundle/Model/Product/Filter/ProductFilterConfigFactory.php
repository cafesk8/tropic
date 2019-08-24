<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig as BaseProductFilterConfig;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfigFactory as BaseProductFilterConfigFactory;

/**
 * @property  \Shopsys\ShopBundle\Model\Product\Filter\ParameterFilterChoiceRepository $parameterFilterChoiceRepository
 */
class ProductFilterConfigFactory extends BaseProductFilterConfigFactory
{
    /**
     * @param int $domainId
     * @param string $locale
     * @param \Shopsys\FrameworkBundle\Model\Category\Category $category
     * @return \Shopsys\ShopBundle\Model\Product\Filter\ProductFilterConfig
     */
    public function createForCategory($domainId, $locale, Category $category): ProductFilterConfig
    {
        $pricingGroup = $this->currentCustomer->getPricingGroup();
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
     * @return \Shopsys\ShopBundle\Model\Product\Filter\ProductFilterConfig
     */
    public function createForSearch($domainId, $locale, $searchText): ProductFilterConfig
    {
        $parent = parent::createForSearch($domainId, $locale, $searchText);

        return $this->createFromParent($parent, [], []);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig $parent
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoice[] $colorChoices
     * @param \Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoice[] $sizeChoices
     * @return \Shopsys\ShopBundle\Model\Product\Filter\ProductFilterConfig
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

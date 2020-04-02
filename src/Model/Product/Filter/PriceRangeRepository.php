<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Filter\PriceRangeRepository as BasePriceRangeRepository;

/**
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method __construct(\App\Model\Product\ProductRepository $productRepository, \Shopsys\FrameworkBundle\Component\Doctrine\QueryBuilderExtender $queryBuilderExtender)
 * @method \Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange getPriceRangeByProductsQueryBuilder(\Doctrine\ORM\QueryBuilder $productsQueryBuilder, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class PriceRangeRepository extends BasePriceRangeRepository
{
    /**
     * The only difference with the parent method is that here we use getOfferedInCategoryQueryBuilder instead of getListableInCategoryQueryBuilder
     * so variant prices are included in the filter
     *
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Category\Category $category
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange
     */
    public function getPriceRangeInCategory($domainId, PricingGroup $pricingGroup, Category $category)
    {
        $productsQueryBuilder = $this->productRepository->getOfferedInCategoryQueryBuilder(
            $domainId,
            $pricingGroup,
            $category
        );

        return $this->getPriceRangeByProductsQueryBuilder($productsQueryBuilder, $pricingGroup);
    }

    /**
     * The only difference with the parent method is that here we use getOfferedBySearchTextQueryBuilder instead of getListableBySearchTextQueryBuilder
     * so variant prices are included in the filter
     *
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param string $locale
     * @param string|null $searchText
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange
     */
    public function getPriceRangeForSearch($domainId, PricingGroup $pricingGroup, $locale, $searchText)
    {
        $productsQueryBuilder = $this->productRepository
            ->getOfferedBySearchTextQueryBuilder($domainId, $pricingGroup, $locale, $searchText);

        return $this->getPriceRangeByProductsQueryBuilder($productsQueryBuilder, $pricingGroup);
    }
}

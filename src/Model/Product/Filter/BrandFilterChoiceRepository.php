<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Filter\BrandFilterChoiceRepository as BaseBrandFilterChoiceRepository;

/**
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method __construct(\App\Model\Product\ProductRepository $productRepository)
 * @method \App\Model\Product\Brand\Brand[] getBrandFilterChoicesForSearch(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, string $locale, string|null $searchText)
 * @method \App\Model\Product\Brand\Brand[] getBrandsByProductsQueryBuilder(\Doctrine\ORM\QueryBuilder $productsQueryBuilder)
 */
class BrandFilterChoiceRepository extends BaseBrandFilterChoiceRepository
{
    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Product\Flag\Flag[] $onlyFlags
     * @return \App\Model\Product\Brand\Brand[]
     */
    public function getBrandFilterChoicesInCategory($domainId, PricingGroup $pricingGroup, Category $category, array $onlyFlags = [])
    {
        $productsQueryBuilder = $this->productRepository->getListableInCategoryQueryBuilder(
            $domainId,
            $pricingGroup,
            $category,
            $onlyFlags
        );

        return $this->getBrandsByProductsQueryBuilder($productsQueryBuilder);
    }
}

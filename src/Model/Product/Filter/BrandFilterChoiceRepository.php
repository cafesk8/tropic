<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use App\Model\Category\CategoryBrand\CategoryBrand;
use App\Model\Product\Brand\Brand;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * @param \Doctrine\ORM\QueryBuilder $productsQueryBuilder
     * @return \App\Model\Product\Brand\Brand[]
     */
    protected function getBrandsByProductsQueryBuilder(QueryBuilder $productsQueryBuilder)
    {
        $clonedProductsQueryBuilder = clone $productsQueryBuilder;

        $clonedProductsQueryBuilder
            ->select('1')
            ->join('p.brand', 'pb')
            ->andWhere('pb.id = b.id')
            ->resetDQLPart('orderBy');

        $brandsQueryBuilder = $productsQueryBuilder->getEntityManager()->createQueryBuilder();

        $brandsQueryBuilder
            ->select('b')
            ->from(Brand::class, 'b')
            ->leftJoin(CategoryBrand::class, 'cb', Join::WITH, 'b = cb.brand AND cb.category = :category')
            ->andWhere($brandsQueryBuilder->expr()->exists($clonedProductsQueryBuilder))
            ->setParameter('category', $clonedProductsQueryBuilder->getParameter('category'))
            ->orderBy('cb.priority, b.name', 'asc');

        foreach ($clonedProductsQueryBuilder->getParameters() as $parameter) {
            $brandsQueryBuilder->setParameter($parameter->getName(), $parameter->getValue());
        }

        return $brandsQueryBuilder->getQuery()->execute();
    }
}

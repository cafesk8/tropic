<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\Product\Filter\FlagFilterChoiceRepository as BaseFlagFilterChoiceRepository;
use Shopsys\FrameworkBundle\Model\Product\Flag\Flag;

/**
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method __construct(\App\Model\Product\ProductRepository $productRepository)
 * @method \App\Model\Product\Flag\Flag[] getFlagFilterChoicesInCategory(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, string $locale, \App\Model\Category\Category $category)
 * @method \App\Model\Product\Flag\Flag[] getFlagFilterChoicesForSearch(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, string $locale, string|null $searchText)
 */
class FlagFilterChoiceRepository extends BaseFlagFilterChoiceRepository
{
    /**
     * @param \Doctrine\ORM\QueryBuilder $productsQueryBuilder
     * @param mixed $locale
     */
    protected function getVisibleFlagsByProductsQueryBuilder(QueryBuilder $productsQueryBuilder, $locale)
    {
        $clonedProductsQueryBuilder = clone $productsQueryBuilder;

        $clonedProductsQueryBuilder
            ->select('1')
            ->join('p.flags', 'pf')
            ->andWhere('pf.flag = f')
            ->andWhere('f.visible = true')
            ->resetDQLPart('orderBy');

        $flagsQueryBuilder = $productsQueryBuilder->getEntityManager()->createQueryBuilder();
        $flagsQueryBuilder
            ->select('f, ft')
            ->from(Flag::class, 'f')
            ->join('f.translations', 'ft', Join::WITH, 'ft.locale = :locale')
            ->andWhere($flagsQueryBuilder->expr()->exists($clonedProductsQueryBuilder))
            ->orderBy('ft.name', 'asc')
            ->setParameter('locale', $locale);

        foreach ($clonedProductsQueryBuilder->getParameters() as $parameter) {
            $flagsQueryBuilder->setParameter($parameter->getName(), $parameter->getValue());
        }

        return $flagsQueryBuilder->getQuery()->execute();
    }
}

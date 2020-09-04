<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use App\Model\Product\Flag\Flag;
use App\Model\Product\Flag\FlagRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\Product\Filter\FlagFilterChoiceRepository as BaseFlagFilterChoiceRepository;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;

/**
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method \App\Model\Product\Flag\Flag[] getFlagFilterChoicesInCategory(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, string $locale, \App\Model\Category\Category $category)
 * @method \App\Model\Product\Flag\Flag[] getFlagFilterChoicesForSearch(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, string $locale, string|null $searchText)
 */
class FlagFilterChoiceRepository extends BaseFlagFilterChoiceRepository
{
    private FlagRepository $flagRepository;

    private ?Flag $saleFlag = null;

    /**
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \App\Model\Product\Flag\FlagRepository $flagRepository
     */
    public function __construct(ProductRepository $productRepository, FlagRepository $flagRepository)
    {
        parent::__construct($productRepository);
        $this->flagRepository = $flagRepository;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $productsQueryBuilder
     * @param string $locale
     * @return \App\Model\Product\Flag\Flag[]
     */
    protected function getVisibleFlagsByProductsQueryBuilder(QueryBuilder $productsQueryBuilder, $locale)
    {
        $clonedProductsQueryBuilder = clone $productsQueryBuilder;

        $clonedProductsQueryBuilder
            ->select('1')
            ->join('p.flags', 'pf')
            ->andWhere('pf.flag = f')
            ->andWhere('f.visible = true')
            ->andWhere('(pf.activeFrom IS NULL OR pf.activeFrom < CURRENT_DATE()) AND (pf.activeTo IS NULL OR pf.activeTo > :tomorrow)')
            ->setParameter('tomorrow', date('Y-m-d', strtotime('tomorrow')))
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

        /** @var \App\Model\Product\Flag\Flag[] $flags */
        $flags = $flagsQueryBuilder->getQuery()->execute();

        foreach ($flags as $key => $flag) {
            if ($flag->isClearance()) {
                $flags[$key] = $this->getSaleFlag();
            }
        }

        return array_unique($flags, SORT_REGULAR);
    }

    /**
     * @return \App\Model\Product\Flag\Flag|null
     */
    private function getSaleFlag(): ?Flag
    {
        if ($this->saleFlag === null) {
            $this->saleFlag = $this->flagRepository->findSaleFlag();
        }

        return $this->saleFlag;
    }
}

<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Pricing;

use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceRepository as BaseProductManualInputPriceRepository;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductDomain;

class ProductManualInputPriceRepository extends BaseProductManualInputPriceRepository
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup[] $pricingGroups
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPrice[]
     */
    public function findByProductAndPricingGroupsForDomain(Product $product, array $pricingGroups, int $domainId)
    {
        $queryBuilder = $this->getProductManualInputPriceRepository()
            ->createQueryBuilder('pmip')
            ->select('MAX(pmip.inputPrice) as inputPrice, IDENTITY(pmip.pricingGroup) as pricingGroupId, MIN(pd.actionPrice) as actionPrice')
            ->where('pmip.pricingGroup IN (:pricingGroups)')
            ->groupBy('pmip.pricingGroup')
            ->setParameter('pricingGroups', $pricingGroups);

        if ($product->isMainVariant()) {
            $queryBuilder
                ->join(Product::class, 'p', Join::WITH, 'pmip.product = p.id AND p.mainVariant = :mainVariantId')
                ->setParameter('mainVariantId', $product);
        } else {
            $queryBuilder
                ->join(Product::class, 'p', Join::WITH, 'pmip.product = p.id')
                ->andWhere('pmip.product = :product')
                ->setParameter('product', $product->getId());
        }

        $queryBuilder
            ->leftJoin(ProductDomain::class, 'pd', Join::WITH, 'pd.product = p.id AND pd.domainId = :domainId')
            ->setParameter('domainId', $domainId);

        return $queryBuilder->getQuery()->getResult();
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceRepository as BaseProductManualInputPriceRepository;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductDomain;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibility;

/**
 * @method \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPrice[] getByProduct(\App\Model\Product\Product $product)
 * @method \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPrice|null findByProductAndPricingGroup(\App\Model\Product\Product $product, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class ProductManualInputPriceRepository extends BaseProductManualInputPriceRepository
{
    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Pricing\Group\PricingGroup[] $pricingGroups
     * @param int $domainId
     * @param bool $allowSellingDeniedVariants
     * @return array
     */
    public function findByProductAndPricingGroupsForDomain(
        Product $product,
        array $pricingGroups,
        int $domainId,
        bool $allowSellingDeniedVariants = false
    ) {
        $queryBuilder = $this->getProductManualInputPriceRepository()
            ->createQueryBuilder('pmip')
            ->select('MIN(pmip.inputPrice) as inputPrice, MAX(pmip.inputPrice) as maxInputPrice, IDENTITY(pmip.pricingGroup) as pricingGroupId')
            ->where('pmip.pricingGroup IN (:pricingGroups)')
            ->groupBy('pmip.pricingGroup')
            ->setParameter('pricingGroups', $pricingGroups);

        if ($product->isMainVariant()) {
            $queryBuilder
                ->join(Product::class, 'p', Join::WITH, 'pmip.product = p.id AND p.mainVariant = :mainVariantId')
                ->leftJoin(ProductVisibility::class, 'pv', Join::WITH, 'p.id = pv.product');

            if (!$allowSellingDeniedVariants) {
                $queryBuilder->andWhere('p.calculatedSellingDenied = false');
            }

            $queryBuilder
                ->andWhere('pmip.inputPrice > 0')
                ->andWhere('pv.visible = true')
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

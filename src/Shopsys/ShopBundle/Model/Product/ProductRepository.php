<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository as BaseProductRepository;

class ProductRepository extends BaseProductRepository
{
    /**
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param array $ids
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getAllVisibleByIds(int $domainId, PricingGroup $pricingGroup, array $ids): array
    {
        $queryBuilder = $this->getAllVisibleQueryBuilder($domainId, $pricingGroup);
        $queryBuilder
            ->andWhere('p.id IN(:productIds)')
            ->setParameter('productIds', $ids);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param int $transferNumber
     * @return \Shopsys\ShopBundle\Model\Product\Product|null
     */
    public function findByTransferNumber(int $transferNumber): ?Product
    {
        return $this->getProductRepository()->findOneBy(['transferNumber' => $transferNumber]);
    }
}

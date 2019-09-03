<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Pricing\Group;

use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupRepository as BasePricingGroupRepository;

class PricingGroupRepository extends BasePricingGroupRepository
{
    /**
     * @param string $name
     * @param int $domainId
     * @return null|\Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup
     */
    public function getByNameAndDomainId(string $name, int $domainId): ?PricingGroup
    {
        return $this->getPricingGroupRepository()
            ->createQueryBuilder('pg')
            ->where('pg.domainId = :domainId')
            ->andWhere('pg.internalId = :name')
            ->setParameter('domainId', $domainId)
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

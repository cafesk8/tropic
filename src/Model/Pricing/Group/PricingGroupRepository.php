<?php

declare(strict_types=1);

namespace App\Model\Pricing\Group;

use Doctrine\ORM\Query;
use Shopsys\FrameworkBundle\Component\Doctrine\SortableNullsWalker;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupRepository as BasePricingGroupRepository;

/**
 * @method \App\Model\Pricing\Group\PricingGroup getById(int $pricingGroupId)
 * @method \App\Model\Pricing\Group\PricingGroup[] getPricingGroupsByDomainId(int $domainId)
 * @method \App\Model\Pricing\Group\PricingGroup|null findById(int $pricingGroupId)
 * @method \App\Model\Pricing\Group\PricingGroup[] getAllExceptIdByDomainId(int $pricingGroupId, int $domainId)
 * @method bool existsUserWithPricingGroup(\App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method bool existsCustomerUserWithPricingGroup(\App\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class PricingGroupRepository extends BasePricingGroupRepository
{
    /**
     * @param string $name
     * @param int $domainId
     * @return null|\App\Model\Pricing\Group\PricingGroup
     */
    public function findByNameAndDomainId(string $name, int $domainId): ?PricingGroup
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

    /**
     * @param float $discount
     * @param int $userDomainId
     * @return \App\Model\Pricing\Group\PricingGroup|null
     */
    public function findByDiscount(float $discount, int $userDomainId): ?PricingGroup
    {
        return $this->getPricingGroupRepository()->findOneBy(['discount' => $discount, 'domainId' => $userDomainId]);
    }

    /**
     * @return \App\Model\Pricing\Group\PricingGroup[]
     */
    public function getAll()
    {
        return $this->getPricingGroupRepository()
            ->createQueryBuilder('pg')
            ->orderBy('pg.discount', 'asc')
            ->getQuery()
            ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class)
            ->getResult();
    }

    /**
     * @param string $internalId
     * @param int $domainId
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    public function getByInternalIdAndDomainId(string $internalId, int $domainId): PricingGroup
    {
        return $this->getPricingGroupRepository()->findOneBy(['domainId' => $domainId, 'internalId' => $internalId]);
    }
}

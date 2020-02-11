<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Pricing\Group;

use Doctrine\ORM\Query;
use Shopsys\FrameworkBundle\Component\Doctrine\SortableNullsWalker;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupRepository as BasePricingGroupRepository;

/**
 * @method \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup getById(int $pricingGroupId)
 * @method \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup[] getPricingGroupsByDomainId(int $domainId)
 * @method \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup|null findById(int $pricingGroupId)
 * @method \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup[] getAllExceptIdByDomainId(int $pricingGroupId, int $domainId)
 * @method bool existsUserWithPricingGroup(\Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
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

    /**
     * @param float $discount
     * @param int $userDomainId
     * @return \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup|null
     */
    public function findByDiscount(float $discount, int $userDomainId): ?PricingGroup
    {
        return $this->getPricingGroupRepository()->findOneBy(['discount' => $discount, 'domainId' => $userDomainId]);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup[]
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
}

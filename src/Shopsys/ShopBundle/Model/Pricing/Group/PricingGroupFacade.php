<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Pricing\Group;

use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade as BasePricingGroupFacade;

class PricingGroupFacade extends BasePricingGroupFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupRepository
     */
    protected $pricingGroupRepository;

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public function getAllIndexedByDomainIdOrderedByMinimalPrice(): array
    {
        $pricingGroupsByDomainId = [];
        foreach ($this->domain->getAll() as $domain) {
            $domainId = $domain->getId();
            $pricingGroups = $this->pricingGroupRepository->getPricingGroupsByDomainId($domainId);

            usort(
                $pricingGroups,
                function (PricingGroup $first, PricingGroup $second) {
                    if ($first->getMinimalPrice() === null) {
                        return -1;
                    }

                    if ($second->getMinimalPrice() === null) {
                        return 1;
                    }
                    return $first->getMinimalPrice()->compare($second->getMinimalPrice());
                }
            );

            $pricingGroupsByDomainId[$domainId] = $pricingGroups;
        }

        return $pricingGroupsByDomainId;
    }

    /**
     * @param string $name
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup|null
     */
    public function getByNameAndDomainId(string $name, int $domainId): ?PricingGroup
    {
        return $this->pricingGroupRepository->getByNameAndDomainId($name, $domainId);
    }
}

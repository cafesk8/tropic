<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Pricing\Group;

use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade as BasePricingGroupFacade;

/**
 * @property \Shopsys\ShopBundle\Model\Customer\UserRepository $userRepository
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupRepository $pricingGroupRepository, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler $productPriceRecalculationScheduler, \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade, \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository $productVisibilityRepository, \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductCalculatedPriceRepository $productCalculatedPriceRepository, \Shopsys\ShopBundle\Model\Customer\UserRepository $userRepository, \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFactoryInterface $pricingGroupFactory)
 * @method \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup getById(int $pricingGroupId)
 * @method \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup create(\Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupData $pricingGroupData, int $domainId)
 * @method \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup edit(int $pricingGroupId, \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupData $pricingGroupData)
 * @method \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup[] getAll()
 * @method \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup[] getByDomainId(int $domainId)
 * @method \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup[] getAllExceptIdByDomainId(int $id, int $domainId)
 * @method \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup[][] getAllIndexedByDomainId()
 */
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

    /**
     * @param float $discount
     * @param int $userDomainId
     * @return \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup|null
     */
    public function findByDiscount(float $discount, int $userDomainId): ?PricingGroup
    {
        return $this->pricingGroupRepository->findByDiscount($discount, $userDomainId);
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Pricing\Group;

use App\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Pricing\Group\Exception\PricingGroupNotFoundException;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade as BasePricingGroupFacade;

/**
 * @property \App\Model\Customer\User\CustomerUserRepository $customerUserRepository
 * @method \App\Model\Pricing\Group\PricingGroup getById(int $pricingGroupId)
 * @method \App\Model\Pricing\Group\PricingGroup create(\App\Model\Pricing\Group\PricingGroupData $pricingGroupData, int $domainId)
 * @method \App\Model\Pricing\Group\PricingGroup edit(int $pricingGroupId, \App\Model\Pricing\Group\PricingGroupData $pricingGroupData)
 * @method \App\Model\Pricing\Group\PricingGroup[] getAll()
 * @method \App\Model\Pricing\Group\PricingGroup[] getByDomainId(int $domainId)
 * @method \App\Model\Pricing\Group\PricingGroup[] getAllExceptIdByDomainId(int $id, int $domainId)
 * @method \App\Model\Pricing\Group\PricingGroup[][] getAllIndexedByDomainId()
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Pricing\Group\PricingGroupRepository $pricingGroupRepository, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler $productPriceRecalculationScheduler, \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade, \App\Model\Product\ProductVisibilityRepository $productVisibilityRepository, \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductCalculatedPriceRepository $productCalculatedPriceRepository, \App\Model\Customer\User\CustomerUserRepository $customerUserRepository, \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFactoryInterface $pricingGroupFactory, \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher)
 * @method dispatchPricingGroupEvent(\App\Model\Pricing\Group\PricingGroup $pricingGroup, string $eventType)
 * @property \App\Model\Product\ProductVisibilityRepository $productVisibilityRepository
 */
class PricingGroupFacade extends BasePricingGroupFacade
{
    /**
     * @var \App\Model\Pricing\Group\PricingGroupRepository
     */
    protected $pricingGroupRepository;

    /**
     * @var \App\Model\Pricing\Group\PricingGroup[][]
     */
    private $cachedPricingGroupByType = [];

    /**
     * @var \App\Model\Pricing\Group\PricingGroup[]
     */
    private array $cachedDefaultPricingGroupsByDomainId = [];

    /**
     * @param string $name
     * @param int $domainId
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    public function getByNameAndDomainId(string $name, int $domainId): PricingGroup
    {
        if (array_key_exists($domainId, $this->cachedPricingGroupByType) && array_key_exists($name, $this->cachedPricingGroupByType[$domainId])) {
            return $this->cachedPricingGroupByType[$domainId][$name];
        }

        $pricingGroup = $this->pricingGroupRepository->findByNameAndDomainId($name, $domainId);

        if ($pricingGroup === null) {
            throw new PricingGroupNotFoundException('Cannot find pricing group ' . $name . ' on domain ID ' . $domainId);
        }

        $this->cachedPricingGroupByType[$domainId][$name] = $pricingGroup;

        return $pricingGroup;
    }

    /**
     * @param float $discount
     * @param int $userDomainId
     * @return \App\Model\Pricing\Group\PricingGroup|null
     */
    public function findByDiscount(float $discount, int $userDomainId): ?PricingGroup
    {
        return $this->pricingGroupRepository->findByDiscount($discount, $userDomainId);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    public function getOrdinaryCustomerPricingGroup(int $domainId): PricingGroup
    {
        return $this->getByNameAndDomainId(PricingGroup::PRICING_GROUP_ORDINARY_CUSTOMER, $domainId);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    public function getRegisteredCustomerPricingGroup(int $domainId): PricingGroup
    {
        return $this->getByNameAndDomainId(PricingGroup::PRICING_GROUP_REGISTERED_CUSTOMER, $domainId);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    public function getPurchasePricePricingGroup(int $domainId): PricingGroup
    {
        return $this->getByNameAndDomainId(PricingGroup::PRICING_GROUP_PURCHASE_PRICE, $domainId);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    public function getStandardPricePricingGroup(int $domainId): PricingGroup
    {
        return $this->getByNameAndDomainId(PricingGroup::PRICING_GROUP_STANDARD_PRICE, $domainId);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    public function getSalePricePricingGroup(int $domainId): PricingGroup
    {
        return $this->getByNameAndDomainId(PricingGroup::PRICING_GROUP_SALE_PRICE, $domainId);
    }

    /**
     * @return \App\Model\Pricing\Group\PricingGroup[]
     */
    public function getAllOrderedByInternalId(): array
    {
        $pricingGroups = $this->getAll();

        usort($pricingGroups, function (PricingGroup $first, PricingGroup $second) {
            if ($first->isOrdinaryCustomerPricingGroup()) {
                return 0;
            }

            if ($second->isOrdinaryCustomerPricingGroup()) {
                return -1;
            }

            return 1;
        });

        return $pricingGroups;
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    public function getCurrentPricingGroup(?CustomerUser $customerUser): PricingGroup
    {
        if ($customerUser !== null) {
            $pricingGroup = $customerUser->getPricingGroup();
        } else {
            $pricingGroup = $this->getDefaultPricingGroup($this->domain->getId());
        }

        return $pricingGroup;
    }

    /**
     * @param int $domainId
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    public function getDefaultPricingGroup(int $domainId): PricingGroup
    {
        if (isset($this->cachedDefaultPricingGroupsByDomainId[$domainId]) === false) {
            /** @var \App\Model\Pricing\Group\PricingGroup $defaultPricingGroup */
            $defaultPricingGroup = $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($domainId);
            $this->cachedDefaultPricingGroupsByDomainId[$domainId] = $defaultPricingGroup;
        }

        return $this->cachedDefaultPricingGroupsByDomainId[$domainId];
    }
}

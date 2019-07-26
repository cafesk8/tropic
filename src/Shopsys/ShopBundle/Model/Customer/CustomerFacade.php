<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use Shopsys\FrameworkBundle\Model\Customer\CustomerFacade as BaseCustomerFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;

class CustomerFacade extends BaseCustomerFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Customer\UserRepository
     */
    protected $userRepository;

    /**
     * @param int[] $customerIds
     * @return \Shopsys\ShopBundle\Model\Customer\User[]
     */
    public function getUsersByIds(array $customerIds): array
    {
        return $this->userRepository->getUsersByIds($customerIds);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     */
    public function changePricingGroup(User $user, PricingGroup $pricingGroup): void
    {
        $user->setPricingGroup($pricingGroup);
        $this->em->flush($user);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Customer\User[]
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->getAllUsers();
    }
}

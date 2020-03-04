<?php

declare(strict_types=1);

namespace App\Model\Customer\User;

use DateTime;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser as BaseCustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserData as BaseCustomerUserData;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserDataFactory as BaseCustomerUserDataFactory;
use Shopsys\FrameworkBundle\Model\Order\Order;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;

/**
 * @method \App\Model\Customer\User\CustomerUserData createForCustomer(\Shopsys\FrameworkBundle\Model\Customer\Customer $customer)
 */
class CustomerUserDataFactory extends BaseCustomerUserDataFactory
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     */
    public function __construct(PricingGroupSettingFacade $pricingGroupSettingFacade)
    {
        parent::__construct($pricingGroupSettingFacade);
    }

    /**
     * @return \App\Model\Customer\User\CustomerUserData
     */
    public function create(): BaseCustomerUserData
    {
        return new CustomerUserData();
    }

    /**
     * @param int $domainId
     * @return \App\Model\Customer\User\CustomerUserData
     */
    public function createForDomainId(int $domainId): BaseCustomerUserData
    {
        $customerUserData = new CustomerUserData();
        $this->fillForDomainId($customerUserData, $domainId);
        $customerUserData->memberOfLoyaltyProgram = false;

        return $customerUserData;
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @return \App\Model\Customer\User\CustomerUserData
     */
    public function createFromCustomerUser(BaseCustomerUser $customerUser): BaseCustomerUserData
    {
        $customerUserData = new CustomerUserData();
        $this->fillFromUser($customerUserData, $customerUser);
        $customerUserData->pricingGroup = $customerUser->getPricingGroup();

        return $customerUserData;
    }

    /**
     * @param \App\Model\Customer\User\CustomerUserData $customerUserData
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     */
    protected function fillFromUser(BaseCustomerUserData $customerUserData, BaseCustomerUser $customerUser): void
    {
        parent::fillFromUser($customerUserData, $customerUser);
        $customerUserData->memberOfLoyaltyProgram = $customerUser->isMemberOfLoyaltyProgram();
        $customerUserData->pricingGroupUpdatedAt = $customerUser->getPricingGroupUpdatedAt();
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param string $password
     * @param int $domainId
     * @return \App\Model\Customer\User\CustomerUserData
     */
    public function createUserDataFromOrder(Order $order, string $password, int $domainId): CustomerUserData
    {
        $customerUserData = $this->createForDomainId($domainId);
        $customerUserData->firstName = $order->getFirstName();
        $customerUserData->lastName = $order->getLastName();
        $customerUserData->email = $order->getEmail();
        $customerUserData->telephone = $order->getTelephone();
        $customerUserData->createdAt = new DateTime();
        $customerUserData->password = $password;

        return $customerUserData;
    }
}

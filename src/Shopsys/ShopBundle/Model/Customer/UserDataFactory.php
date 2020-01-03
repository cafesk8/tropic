<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use DateTime;
use Shopsys\FrameworkBundle\Model\Customer\User as BaseUser;
use Shopsys\FrameworkBundle\Model\Customer\UserData as BaseUserData;
use Shopsys\FrameworkBundle\Model\Customer\UserDataFactory as BaseUserDataFactory;
use Shopsys\FrameworkBundle\Model\Order\Order;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;

class UserDataFactory extends BaseUserDataFactory
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     */
    public function __construct(PricingGroupSettingFacade $pricingGroupSettingFacade)
    {
        parent::__construct($pricingGroupSettingFacade);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Customer\UserData
     */
    public function create(): BaseUserData
    {
        return new UserData();
    }

    /**
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Customer\UserData
     */
    public function createForDomainId(int $domainId): BaseUserData
    {
        $userData = new UserData();
        $this->fillForDomainId($userData, $domainId);
        $userData->memberOfBushmanClub = false;

        return $userData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     * @return \Shopsys\ShopBundle\Model\Customer\UserData
     */
    public function createFromUser(BaseUser $user): BaseUserData
    {
        $userData = new UserData();
        $this->fillFromUser($userData, $user);

        return $userData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\UserData $userData
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     */
    protected function fillFromUser(BaseUserData $userData, BaseUser $user): void
    {
        parent::fillFromUser($userData, $user);
        $userData->memberOfBushmanClub = $user->isMemberOfBushmanClub();
        $userData->ean = $user->getEan();
        $userData->pricingGroupUpdatedAt = $user->getPricingGroupUpdatedAt();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param string $password
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Customer\UserData
     */
    public function createUserDataFromOrder(Order $order, string $password, int $domainId): UserData
    {
        $userData = $this->createForDomainId($domainId);
        $userData->firstName = $order->getFirstName();
        $userData->lastName = $order->getLastName();
        $userData->email = $order->getEmail();
        $userData->telephone = $order->getTelephone();
        $userData->createdAt = new DateTime();
        $userData->password = $password;

        return $userData;
    }
}

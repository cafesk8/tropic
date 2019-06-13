<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use Shopsys\FrameworkBundle\Component\Utils\Utils;
use Shopsys\FrameworkBundle\Model\Customer\CustomerData;
use Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactory as BaseCustomerDataFactory;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Order\Order;

class CustomerDataFactory extends BaseCustomerDataFactory
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User $user
     * @param \Shopsys\FrameworkBundle\Model\Order\Order $order
     * @return \Shopsys\FrameworkBundle\Model\Customer\CustomerData
     */
    public function createAmendedByOrder(User $user, Order $order): CustomerData
    {
        $billingAddress = $user->getBillingAddress();
        $deliveryAddress = $user->getDeliveryAddress();

        $customerData = $this->createFromUser($user);

        $customerData->userData->firstName = Utils::ifNull($user->getFirstName(), $order->getFirstName());
        $customerData->userData->lastName = Utils::ifNull($user->getLastName(), $order->getLastName());
        $customerData->billingAddressData = $this->getAmendedBillingAddressDataByOrder($order, $billingAddress);

        if ($order->getTransport()->isPickupPlace() === false && $order->getTransport()->isChooseStore() === false) {
            $customerData->deliveryAddressData = $this->getAmendedDeliveryAddressDataByOrder($order, $deliveryAddress);
        }

        return $customerData;
    }
}

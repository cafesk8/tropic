<?php

declare(strict_types=1);

namespace App\Model\Customer;

use Shopsys\FrameworkBundle\Model\Customer\BillingAddress;
use Shopsys\FrameworkBundle\Model\Customer\CustomerData;
use Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactory as BaseCustomerDataFactory;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Order\Order;

/**
 * @method \Shopsys\FrameworkBundle\Model\Customer\CustomerData createFromUser(\App\Model\Customer\User $user)
 * @property \App\Model\Customer\UserDataFactory $userDataFactory
 * @property \App\Model\Customer\DeliveryAddressDataFactory $deliveryAddressDataFactory
 * @property \App\Model\Customer\BillingAddressDataFactory $billingAddressDataFactory
 */
class CustomerDataFactory extends BaseCustomerDataFactory
{
    /**
     * @param \App\Model\Customer\User $user
     * @param \App\Model\Order\Order $order
     * @return \Shopsys\FrameworkBundle\Model\Customer\CustomerData
     */
    public function createAmendedByOrder(User $user, Order $order): CustomerData
    {
        $billingAddress = $user->getBillingAddress();
        $deliveryAddress = $user->getDeliveryAddress();

        $customerData = $this->createFromUser($user);

        $customerData->userData->firstName = $order->getFirstName();
        $customerData->userData->lastName = $order->getLastName();
        $customerData->userData->telephone = $order->getTelephone();
        $customerData->billingAddressData = $this->getAmendedBillingAddressDataByOrder($order, $billingAddress);

        if ($order->getTransport()->isPickupPlace() === false && $order->getTransport()->isChooseStore() === false) {
            $customerData->deliveryAddressData = $this->getAmendedDeliveryAddressDataByOrder($order, $deliveryAddress);
        }

        return $customerData;
    }

    /**
     * Method has to be overwritten, because we always want to update customer data with data from an order
     *
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Customer\BillingAddress $billingAddress
     * @return \Shopsys\FrameworkBundle\Model\Customer\BillingAddressData
     */
    protected function getAmendedBillingAddressDataByOrder(Order $order, BillingAddress $billingAddress)
    {
        $billingAddressData = $this->billingAddressDataFactory->createFromBillingAddress($billingAddress);

        $billingAddressData->companyCustomer = $order->getCompanyNumber() !== null;
        $billingAddressData->companyName = $order->getCompanyName();
        $billingAddressData->companyNumber = $order->getCompanyNumber();
        $billingAddressData->companyTaxNumber = $order->getCompanyTaxNumber();
        $billingAddressData->street = $order->getStreet();
        $billingAddressData->city = $order->getCity();
        $billingAddressData->postcode = $order->getPostcode();
        $billingAddressData->country = $order->getCountry();

        return $billingAddressData;
    }

    /**
     * Method has to be overwritten, because we always want to update customer data with data from an order
     *
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Customer\DeliveryAddress|null $deliveryAddress
     * @return \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData
     */
    protected function getAmendedDeliveryAddressDataByOrder(Order $order, ?DeliveryAddress $deliveryAddress = null)
    {
        if ($deliveryAddress === null) {
            $deliveryAddressData = $this->deliveryAddressDataFactory->create();
        } else {
            $deliveryAddressData = $this->deliveryAddressDataFactory->createFromDeliveryAddress($deliveryAddress);
        }

        $deliveryAddressData->addressFilled = true;
        $deliveryAddressData->street = $order->getDeliveryStreet();
        $deliveryAddressData->city = $order->getDeliveryCity();
        $deliveryAddressData->postcode = $order->getDeliveryPostcode();
        $deliveryAddressData->country = $order->getDeliveryCountry();
        $deliveryAddressData->companyName = $order->getDeliveryCompanyName();
        $deliveryAddressData->firstName = $order->getDeliveryFirstName();
        $deliveryAddressData->lastName = $order->getDeliveryLastName();
        $deliveryAddressData->telephone = $order->getDeliveryTelephone();

        return $deliveryAddressData;
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Customer\CustomerData
     */
    public function createForDomainId(int $domainId): CustomerData
    {
        return new CustomerData(
            $this->billingAddressDataFactory->create(),
            $this->deliveryAddressDataFactory->create(),
            $this->userDataFactory->createForDomainId($domainId)
        );
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param string $newPassword
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Customer\CustomerData
     */
    public function createFromOrder(Order $order, string $newPassword, int $domainId): CustomerData
    {
        $userData = $this->userDataFactory->createUserDataFromOrder($order, $newPassword, $domainId);

        $deliveryAddressData = $this->deliveryAddressDataFactory->create();
        $billingAddressData = null;
        /** @var \App\Model\Transport\Transport $transport */
        $transport = $order->getTransport();

        if ($transport->isPickupPlaceType() === false) {
            $deliveryAddressData = $this->deliveryAddressDataFactory->createFromOrder($order);
        }

        if ($order->isDeliveryAddressSameAsBillingAddress() === false) {
            $billingAddressData = $this->billingAddressDataFactory->createFromOrder($order);
        }

        return new CustomerData($billingAddressData, $deliveryAddressData, $userData);
    }
}

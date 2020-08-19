<?php

declare(strict_types=1);

namespace App\Model\Customer\User;

use Shopsys\FrameworkBundle\Model\Customer\BillingAddress;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress;
use  Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateDataFactory as BaseCustomerDataFactory;
use Shopsys\FrameworkBundle\Model\Order\Order;

/**
 * @method \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData createFromCustomerUser(\App\Model\Customer\User\CustomerUser $customerUser)
 * @property \App\Model\Customer\User\CustomerUserDataFactory $customerUserDataFactory
 * @property \App\Model\Customer\DeliveryAddressDataFactory $deliveryAddressDataFactory
 * @property \App\Model\Customer\BillingAddressDataFactory $billingAddressDataFactory
 */
class CustomerUserUpdateDataFactory extends BaseCustomerDataFactory
{
    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Customer\DeliveryAddress|null $deliveryAddress
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData
     */
    public function createAmendedByOrder(CustomerUser $customerUser, Order $order, ?DeliveryAddress $deliveryAddress): CustomerUserUpdateData
    {
        $billingAddress = $customerUser->getCustomer()->getBillingAddress();

        $customerUserUpdateData = $this->createFromCustomerUser($customerUser);

        $customerUserUpdateData->customerUserData->firstName = $order->getFirstName();
        $customerUserUpdateData->customerUserData->lastName = $order->getLastName();
        $customerUserUpdateData->customerUserData->telephone = $order->getTelephone();

        if (!$order->getTransport()->isPickupPlaceType()) {
            $customerUserUpdateData->billingAddressData = $this->getAmendedBillingAddressDataByOrder($order, $billingAddress);
            $customerUserUpdateData->deliveryAddressData = $this->getAmendedDeliveryAddressDataByOrder($order, $deliveryAddress);
        } elseif (!$order->isDeliveryAddressSameAsBillingAddress()) {
            $customerUserUpdateData->billingAddressData = $this->getAmendedBillingAddressDataByOrder($order, $billingAddress);
        }

        return $customerUserUpdateData;
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
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData
     */
    public function createForDomainId(int $domainId): CustomerUserUpdateData
    {
        return new CustomerUserUpdateData(
            $this->billingAddressDataFactory->create(),
            $this->deliveryAddressDataFactory->create(),
            $this->customerUserDataFactory->createForDomainId($domainId)
        );
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param string $newPassword
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData
     */
    public function createFromOrder(Order $order, string $newPassword, int $domainId): CustomerUserUpdateData
    {
        $customerUserData = $this->customerUserDataFactory->createUserDataFromOrder($order, $newPassword, $domainId);

        $deliveryAddressData = $this->deliveryAddressDataFactory->create();
        $billingAddressData = $this->billingAddressDataFactory->create();
        /** @var \App\Model\Transport\Transport $transport */
        $transport = $order->getTransport();

        if ($transport->isPickupPlaceType() === false) {
            $deliveryAddressData = $this->deliveryAddressDataFactory->createFromOrder($order);
        }

        if ($order->isDeliveryAddressSameAsBillingAddress() === false) {
            $billingAddressData = $this->billingAddressDataFactory->createFromOrder($order);
        }

        return new CustomerUserUpdateData($billingAddressData, $deliveryAddressData, $customerUserData);
    }
}

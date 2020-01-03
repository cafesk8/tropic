<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use Shopsys\FrameworkBundle\Model\Customer\BillingAddress;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddressData;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressDataFactory as BaseDeliveryAddressDataFactory;
use Shopsys\FrameworkBundle\Model\Order\Order;

class DeliveryAddressDataFactory extends BaseDeliveryAddressDataFactory
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddress $billingAddress
     * @return \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData
     */
    public function createFromBillingAddress(BillingAddress $billingAddress): DeliveryAddressData
    {
        $deliveryAddressData = $this->create();
        $this->fillFromBillingAddress($deliveryAddressData, $billingAddress);

        return $deliveryAddressData;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData $deliveryAddressData
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddress $billingAddress
     */
    protected function fillFromBillingAddress(DeliveryAddressData $deliveryAddressData, BillingAddress $billingAddress): void
    {
        $deliveryAddressData->addressFilled = true;
        $deliveryAddressData->companyName = $billingAddress->getCompanyName();
        $deliveryAddressData->street = $billingAddress->getStreet();
        $deliveryAddressData->city = $billingAddress->getCity();
        $deliveryAddressData->postcode = $billingAddress->getPostcode();
        $deliveryAddressData->country = $billingAddress->getCountry();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddressData $billingAddressData
     * @return \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData
     */
    public function createFromBillingAddressData(BillingAddressData $billingAddressData): DeliveryAddressData
    {
        $deliveryAddressData = $this->create();
        $this->fillFromBillingAddressData($deliveryAddressData, $billingAddressData);

        return $deliveryAddressData;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData $deliveryAddressData
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddressData $billingAddressData
     */
    protected function fillFromBillingAddressData(DeliveryAddressData $deliveryAddressData, BillingAddressData $billingAddressData): void
    {
        $deliveryAddressData->addressFilled = true;
        $deliveryAddressData->companyName = $billingAddressData->companyName;
        $deliveryAddressData->street = $billingAddressData->street;
        $deliveryAddressData->city = $billingAddressData->city;
        $deliveryAddressData->postcode = $billingAddressData->postcode;
        $deliveryAddressData->country = $billingAddressData->country;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Order $order
     * @return \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData
     */
    public function createFromOrder(Order $order): DeliveryAddressData
    {
        $deliveryAddressData = $this->create();

        $deliveryAddressData->addressFilled = true;
        $deliveryAddressData->companyName = $order->getDeliveryCompanyName();
        $deliveryAddressData->street = $order->getDeliveryStreet();
        $deliveryAddressData->city = $order->getDeliveryCity();
        $deliveryAddressData->postcode = $order->getDeliveryPostcode();
        $deliveryAddressData->country = $order->getDeliveryCountry();

        return $deliveryAddressData;
    }
}

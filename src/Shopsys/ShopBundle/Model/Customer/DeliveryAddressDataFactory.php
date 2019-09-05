<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use Shopsys\FrameworkBundle\Model\Customer\BillingAddress;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressDataFactory as BaseDeliveryAddressDataFactory;

class DeliveryAddressDataFactory extends BaseDeliveryAddressDataFactory
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress $billingAddress
     * @param \Shopsys\ShopBundle\Model\Customer\BillingAddress $deliveryAddress
     * @return \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData
     */
    public function createFromBillingAddress(BillingAddress $billingAddress): DeliveryAddressData
    {
        $deliveryAddressData = $this->create();
        $this->fillFromBillingAddress($deliveryAddressData, $billingAddress);

        return $deliveryAddressData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\DeliveryAddressData $deliveryAddressData
     * @param \Shopsys\ShopBundle\Model\Customer\BillingAddress $billingAddress
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
}

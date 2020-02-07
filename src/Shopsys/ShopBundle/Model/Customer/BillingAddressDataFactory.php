<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use Shopsys\FrameworkBundle\Model\Customer\BillingAddressData;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddressDataFactory as BaseBillingAddressDataFactory;
use Shopsys\FrameworkBundle\Model\Order\Order;

/**
 * @method \Shopsys\FrameworkBundle\Model\Customer\BillingAddressData createFromBillingAddress(\Shopsys\ShopBundle\Model\Customer\BillingAddress $billingAddress)
 * @method fillFromBillingAddress(\Shopsys\FrameworkBundle\Model\Customer\BillingAddressData $billingAddressData, \Shopsys\ShopBundle\Model\Customer\BillingAddress $billingAddress)
 */
class BillingAddressDataFactory extends BaseBillingAddressDataFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return \Shopsys\FrameworkBundle\Model\Customer\BillingAddressData
     */
    public function createFromOrder(Order $order): BillingAddressData
    {
        $billingAddressData = $this->create();

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
}

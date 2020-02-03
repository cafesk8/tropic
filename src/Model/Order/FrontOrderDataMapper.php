<?php

declare(strict_types=1);

namespace App\Model\Order;

use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Order\FrontOrderData;
use Shopsys\FrameworkBundle\Model\Order\FrontOrderDataMapper as BaseFrontOrderDataMapper;
use Shopsys\FrameworkBundle\Model\Order\Order;

/**
 * @method prefillFrontFormDataFromCustomer(\App\Model\Order\FrontOrderData $frontOrderData, \App\Model\Customer\User $user)
 */
class FrontOrderDataMapper extends BaseFrontOrderDataMapper
{
    /**
     * @param \App\Model\Order\FrontOrderData $frontOrderData
     * @param \App\Model\Order\Order $order
     */
    protected function prefillTransportAndPaymentFromOrder(FrontOrderData $frontOrderData, Order $order)
    {
        $frontOrderData->transport = $order->getTransport()->isDeleted() ? null : $order->getTransport();
        $frontOrderData->payment = $order->getPayment()->isDeleted() ? null : $order->getPayment();
        $frontOrderData->pickupPlace = $order->getPickupPlace();
    }

    /**
     * @param \App\Model\Order\FrontOrderData $frontOrderData
     * @param \App\Model\Customer\User $user
     * @param \App\Model\Order\Order|null $order
     */
    public function prefillFrontFormData(FrontOrderData $frontOrderData, User $user, ?Order $order)
    {
        $this->prefillFrontFormDataFromCustomer($frontOrderData, $user);

        $deliveryAddress = $user->getDeliveryAddress();

        if ($deliveryAddress === null) {
            $billingAddress = $user->getBillingAddress();
            $frontOrderData->deliveryFirstName = $user->getFirstName();
            $frontOrderData->deliveryLastName = $user->getLastName();
            $frontOrderData->deliveryCompanyName = $billingAddress->getCompanyName();
            $frontOrderData->deliveryTelephone = $user->getTelephone();
            $frontOrderData->deliveryStreet = $billingAddress->getStreet();
            $frontOrderData->deliveryCity = $billingAddress->getCity();
            $frontOrderData->deliveryPostcode = $billingAddress->getPostcode();
            $frontOrderData->deliveryCountry = $billingAddress->getCountry();
        }
        $frontOrderData->deliveryAddressSameAsBillingAddress = true;
    }
}

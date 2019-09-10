<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use Shopsys\FrameworkBundle\Model\Order\FrontOrderData;
use Shopsys\FrameworkBundle\Model\Order\FrontOrderDataMapper as BaseFrontOrderDataMapper;
use Shopsys\FrameworkBundle\Model\Order\Order;

class FrontOrderDataMapper extends BaseFrontOrderDataMapper
{
    /**
     * @param \Shopsys\ShopBundle\Model\Order\FrontOrderData $frontOrderData
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     */
    protected function prefillTransportAndPaymentFromOrder(FrontOrderData $frontOrderData, Order $order)
    {
        $frontOrderData->transport = $order->getTransport()->isDeleted() ? null : $order->getTransport();
        $frontOrderData->payment = $order->getPayment()->isDeleted() ? null : $order->getPayment();
        $frontOrderData->pickupPlace = $order->getPickupPlace();
    }
}

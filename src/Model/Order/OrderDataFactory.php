<?php

declare(strict_types=1);

namespace App\Model\Order;

use Shopsys\FrameworkBundle\Model\Order\Order as BaseOrder;
use Shopsys\FrameworkBundle\Model\Order\OrderData as BaseOrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderDataFactory as BaseOrderDataFactory;

class OrderDataFactory extends BaseOrderDataFactory
{
    /**
     * @return \App\Model\Order\OrderData
     */
    public function create(): BaseOrderData
    {
        return new OrderData();
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return \App\Model\Order\OrderData
     */
    public function createFromOrder(BaseOrder $order): BaseOrderData
    {
        $orderData = new OrderData();
        $this->fillFromOrder($orderData, $order);

        return $orderData;
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
     * @param \App\Model\Order\Order $order
     */
    protected function fillFromOrder(BaseOrderData $orderData, BaseOrder $order)
    {
        parent::fillFromOrder($orderData, $order);

        $orderData->goPayTransactions = $order->getGoPayTransactions();
        $orderData->payPalId = $order->getPayPalId();
        $orderData->payPalStatus = $order->getPayPalStatus();
        $orderData->exportedAt = $order->getExportedAt();
        $orderData->exportStatus = $order->getExportStatus();
        $orderData->mallOrderId = $order->getMallOrderId();
        $orderData->mallStatus = $order->getMallStatus();
        $orderData->statusCheckedAt = $order->getStatusCheckedAt();
        $orderData->gtmCoupons[] = $order->getGtmCoupons();
        $orderData->store = $order->getStore();
        $orderData->pickupPlace = $order->getPickupPlace();
        $orderData->memberOfLoyaltyProgram = $order->isMemberOfLoyaltyProgram();
        $orderData->transportType = $order->getTransportType();
        $orderData->promoCodesCodes[] = $order->getPromoCodesCodes();
        $orderData->trackingNumber = $order->getTrackingNumber();
    }
}

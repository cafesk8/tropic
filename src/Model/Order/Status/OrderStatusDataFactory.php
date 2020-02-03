<?php

declare(strict_types=1);

namespace App\Model\Order\Status;

use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatus;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusData as BaseOrderStatusData;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusDataFactory as BaseOrderStatusDataFactory;

class OrderStatusDataFactory extends BaseOrderStatusDataFactory
{
    /**
     * @return \App\Model\Order\Status\OrderStatusData
     */
    public function create(): BaseOrderStatusData
    {
        $orderStatusData = new OrderStatusData();
        $this->fillNew($orderStatusData);
        $orderStatusData->checkOrderReadyStatus = false;

        return $orderStatusData;
    }

    /**
     * @param \App\Model\Order\Status\OrderStatus $orderStatus
     * @return \App\Model\Order\Status\OrderStatusData
     */
    public function createFromOrderStatus(OrderStatus $orderStatus): BaseOrderStatusData
    {
        $orderStatusData = new OrderStatusData();
        $this->fillFromOrderStatus($orderStatusData, $orderStatus);

        return $orderStatusData;
    }

    /**
     * @param \App\Model\Order\Status\OrderStatusData $orderStatusData
     * @param \App\Model\Order\Status\OrderStatus $orderStatus
     */
    public function fillFromOrderStatus(BaseOrderStatusData $orderStatusData, OrderStatus $orderStatus)
    {
        parent::fillFromOrderStatus($orderStatusData, $orderStatus);
        $orderStatusData->transferStatus = $orderStatus->getTransferStatus();
        $orderStatusData->smsAlertType = $orderStatus->getSmsAlertType();
        $orderStatusData->checkOrderReadyStatus = $orderStatus->isCheckOrderReadyStatus();
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Order\Item;

use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem as BaseOrderItem;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemData as BaseOrderItemData;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemDataFactory as BaseOrderItemDataFactory;

class OrderItemDataFactory extends BaseOrderItemDataFactory
{
    /**
     * @return \App\Model\Order\Item\OrderItemData
     */
    public function create(): BaseOrderItemData
    {
        $orderItemData = new OrderItemData();

        $this->fillNew($orderItemData);

        return $orderItemData;
    }

    /**
     * @param \App\Model\Order\Item\OrderItem $orderItem
     * @return \App\Model\Order\Item\OrderItemData
     */
    public function createFromOrderItem(BaseOrderItem $orderItem): BaseOrderItemData
    {
        $orderItemData = new OrderItemData();
        $this->fillFromOrderItem($orderItemData, $orderItem);
        $this->addFieldsByOrderItemType($orderItemData, $orderItem);

        return $orderItemData;
    }

    /**
     * @param \App\Model\Order\Item\OrderItemData $orderItemData
     */
    private function fillNew(OrderItemData $orderItemData): void
    {
        $orderItemData->preparedQuantity = 0;
    }
}

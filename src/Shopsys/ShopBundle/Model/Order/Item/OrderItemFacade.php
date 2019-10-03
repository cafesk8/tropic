<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Item;

use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFacade as BaseOrderItemFacade;
use Shopsys\ShopBundle\Model\Order\Order;

class OrderItemFacade extends BaseOrderItemFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderRepository
     */
    protected $orderRepository;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param string $orderItemEan
     * @param int $quantity
     */
    public function setOrderItemPreparedQuantity(Order $order, string $orderItemEan, int $quantity): void
    {
        /** @var \Shopsys\ShopBundle\Model\Order\Item\OrderItem $orderItem */
        $orderItems = $this->orderRepository->findOrderItemsByEan($order, $orderItemEan);

        foreach ($orderItems as $orderItem) {
            $orderItem->setPreparedQuantity($quantity);
            $this->em->flush($orderItem);
        }
    }
}

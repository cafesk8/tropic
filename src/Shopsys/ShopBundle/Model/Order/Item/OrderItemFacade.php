<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Item;

use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFacade as BaseOrderItemFacade;

class OrderItemFacade extends BaseOrderItemFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderRepository
     */
    protected $orderRepository;

    /**
     * @param string $orderItemEan
     * @param int $quantity
     */
    public function setOrderItemPreparedQuantity(string $orderItemEan, int $quantity): void
    {
        /** @var \Shopsys\ShopBundle\Model\Order\Item\OrderItem $orderItem */
        $orderItem = $this->orderRepository->findOrderItemByEan($orderItemEan);

        if ($orderItem !== null) {
            $orderItem->setPreparedQuantity($quantity);
            $this->em->flush($orderItem);
        }
    }
}

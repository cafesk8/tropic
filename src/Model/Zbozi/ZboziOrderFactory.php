<?php

declare(strict_types=1);

namespace App\Model\Zbozi;

use App\Model\Order\Order;
use Soukicz\Zbozicz\CartItem;
use Soukicz\Zbozicz\Order as ZboziOrder;

class ZboziOrderFactory
{
    /**
     * @param \App\Model\Order\Order $order
     * @return \Soukicz\Zbozicz\Order
     */
    public function createFromOrder(Order $order): ZboziOrder
    {
        $orderTransport = $order->getOrderTransport();
        $orderPayment = $order->getOrderPayment();

        $zboziOrder = new ZboziOrder($order->getNumber());
        $zboziOrder->setEmail($order->getEmail());
        $zboziOrder->setDeliveryType((string)$order->getOrderTransport()->getTransport()->getZboziType());
        $zboziOrder->setDeliveryPrice(
            (float)$orderTransport->getTotalPriceWithVat()->getAmount() + (float)$orderPayment->getTotalPriceWithVat()->getAmount()
        );
        $zboziOrder->setPaymentType($orderPayment->getName());

        foreach ($order->getProductItems() as $item) {
            $product = $item->getProduct();
            $zboziItem = new CartItem();
            $zboziItem->setId((string)$product->getId());
            $zboziItem->setName($product->getName());
            $zboziItem->setUnitPrice((float)$item->getPriceWithVat()->getAmount());
            $zboziItem->setQuantity($item->getQuantity());
            $zboziOrder->addCartItem($zboziItem);
        }

        return $zboziOrder;
    }
}
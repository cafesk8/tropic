<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Item;

use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem as BaseOrderItem;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactory as BaseOrderItemFactory;
use Shopsys\FrameworkBundle\Model\Order\Order;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Product;

class OrderItemFactory extends BaseOrderItemFactory
{
    /**
     * @inheritDoc
     */
    public function createProduct(
        Order $order,
        string $name,
        Price $price,
        string $vatPercent,
        int $quantity,
        ?string $unitName,
        ?string $catnum,
        ?Product $product = null
    ): BaseOrderItem {
        /** @var \Shopsys\ShopBundle\Model\Order\Item\OrderItem $orderProduct */
        $orderProduct = parent::createProduct($order, $name, $price, $vatPercent, $quantity, $unitName, $catnum, $product);

        if ($product !== null) {
            $orderProduct->setEan($product->getEan());
        }

        return $orderProduct;
    }

    /**
     * @param string $name
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param \Shopsys\ShopBundle\Model\Order\Item\OrderItem $orderItem
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\OrderItem
     */
    public function createPromoCode(
        string $name,
        Price $price,
        OrderItem $orderItem
    ): BaseOrderItem {
        /** @var \Shopsys\ShopBundle\Model\Order\Item\OrderItem $orderDiscount */
        $orderDiscount = new OrderItem(
            $orderItem->getOrder(),
            $name,
            $price,
            $orderItem->getVatPercent(),
            1,
            OrderItem::TYPE_PROMO_CODE,
            null,
            null
        );

        $orderDiscount->setMainOrderItem($orderItem);

        return $orderDiscount;
    }
}

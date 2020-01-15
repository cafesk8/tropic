<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Item;

use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem as BaseOrderItem;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactory as BaseOrderItemFactory;
use Shopsys\FrameworkBundle\Model\Order\Order;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProduct;

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

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Order $order
     * @param string $name
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $certificatePrice
     * @param string $certificateSku
     * @param string $vatPercent
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\OrderItem
     */
    public function createGiftCertificate(
        Order $order,
        string $name,
        Price $certificatePrice,
        string $certificateSku,
        string $vatPercent
    ): BaseOrderItem {
        /** @var \Shopsys\ShopBundle\Model\Order\Item\OrderItem $orderCertification */
        $orderCertification = new OrderItem(
            $order,
            $name,
            $certificatePrice->inverse(),
            $vatPercent,
            1,
            OrderItem::TYPE_GIFT_CERTIFICATE,
            null,
            $certificateSku
        );

        // ???
        $orderCertification->setEan($certificateSku);
        $orderCertification->setTotalPrice($certificatePrice->inverse());

        return $orderCertification;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Order $order
     * @param string $name
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param string $vatPercent
     * @param int $quantity
     * @param string|null $unitName
     * @param string|null $catnum
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $gift
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $totalPrice
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\OrderItem
     */
    public function createGift(
        Order $order,
        string $name,
        Price $price,
        string $vatPercent,
        int $quantity,
        ?string $unitName,
        ?string $catnum,
        ?Product $gift = null,
        ?Price $totalPrice = null
    ): BaseOrderItem {
        $orderProductGift = new OrderItem(
            $order,
            $name,
            $price,
            $vatPercent,
            $quantity,
            OrderItem::TYPE_GIFT,
            $unitName,
            $catnum
        );

        $orderProductGift->setGift($gift);
        $orderProductGift->setEan($gift->getEan());
        $orderProductGift->setTotalPrice($totalPrice);

        return $orderProductGift;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Order $order
     * @param string $name
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param string $vatPercent
     * @param int $quantity
     * @param string|null $unitName
     * @param string|null $catnum
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $totalPrice
     * @param \Shopsys\ShopBundle\Model\Order\Item\PromoProduct|null $promoProduct
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\OrderItem
     */
    public function createPromoProduct(
        Order $order,
        string $name,
        Price $price,
        string $vatPercent,
        int $quantity,
        ?string $unitName,
        ?string $catnum,
        Product $product,
        Price $totalPrice,
        PromoProduct $promoProduct
    ): BaseOrderItem {
        $promoProductOrderItem = new OrderItem(
            $order,
            $name,
            $price,
            $vatPercent,
            $quantity,
            OrderItem::TYPE_PROMO_PRODUCT,
            $unitName,
            $catnum
        );

        $promoProductOrderItem->setPromoProduct($product, $promoProduct);
        $promoProductOrderItem->setEan($product->getEan());
        $promoProductOrderItem->setTotalPrice($totalPrice);

        return $promoProductOrderItem;
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Order\Item;

use App\Model\Order\Order;
use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem as BaseOrderItem;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactory as BaseOrderItemFactory;
use Shopsys\FrameworkBundle\Model\Order\Order as BaseOrder;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;

/**
 * @method \App\Model\Order\Item\OrderItem createPayment(\App\Model\Order\Order $order, string $name, \Shopsys\FrameworkBundle\Model\Pricing\Price $price, string $vatPercent, int $quantity, \App\Model\Payment\Payment $payment)
 * @method \App\Model\Order\Item\OrderItem createTransport(\App\Model\Order\Order $order, string $name, \Shopsys\FrameworkBundle\Model\Pricing\Price $price, string $vatPercent, int $quantity, \App\Model\Transport\Transport $transport)
 */
class OrderItemFactory extends BaseOrderItemFactory
{
    /**
     * @param \App\Model\Order\Order $order
     * @param string $name
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param string $vatPercent
     * @param int $quantity
     * @param string|null $unitName
     * @param string|null $catnum
     * @param \App\Model\Product\Product|null $product
     * @param bool|null $saleItem
     * @return \App\Model\Order\Item\OrderItem
     */
    public function createProduct(
        BaseOrder $order,
        string $name,
        Price $price,
        string $vatPercent,
        int $quantity,
        ?string $unitName,
        ?string $catnum,
        ?BaseProduct $product = null,
        ?bool $saleItem = false
    ): BaseOrderItem {
        $orderProduct = new OrderItem(
            $order,
            $name,
            $price,
            $vatPercent,
            $quantity,
            OrderItem::TYPE_PRODUCT,
            $unitName,
            $catnum,
            0,
            $saleItem
        );

        $orderProduct->setProduct($product);

        if ($product !== null) {
            $orderProduct->setEan($product->getEan());
        }

        return $orderProduct;
    }

    /**
     * @param string $name
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param \App\Model\Order\Item\OrderItem $orderItem
     * @return \App\Model\Order\Item\OrderItem
     */
    public function createPromoCode(
        string $name,
        Price $price,
        OrderItem $orderItem
    ): BaseOrderItem {
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
     * @param string $name
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param \App\Model\Order\Item\OrderItem $orderItem
     * @return \App\Model\Order\Item\OrderItem
     */
    public function createOrderDiscountLevel(
        string $name,
        Price $price,
        OrderItem $orderItem
    ): BaseOrderItem {
        $orderDiscountLevel = new OrderItem(
            $orderItem->getOrder(),
            $name,
            $price,
            $orderItem->getVatPercent(),
            1,
            OrderItem::TYPE_ORDER_DISCOUNT_LEVEL,
            null,
            null
        );

        $orderDiscountLevel->setMainOrderItem($orderItem);

        return $orderDiscountLevel;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param string $name
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $certificatePrice
     * @param string|null $certificateSku
     * @param string $vatPercent
     * @return \App\Model\Order\Item\OrderItem
     */
    public function createGiftCertificate(
        Order $order,
        string $name,
        Price $certificatePrice,
        ?string $certificateSku,
        string $vatPercent
    ): BaseOrderItem {
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
     * @param \App\Model\Order\Order $order
     * @param string $name
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param string $vatPercent
     * @param int $quantity
     * @param string|null $unitName
     * @param string|null $catnum
     * @param \App\Model\Product\Product|null $gift
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $totalPrice
     * @return \App\Model\Order\Item\OrderItem
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
     * @param \App\Model\Order\Order $order
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param string $vatPercent
     * @return \App\Model\Order\Item\OrderItem
     */
    public function createTransportFee(Order $order, Price $price, string $vatPercent): OrderItem
    {
        return new OrderItem(
            $order,
            t('Příplatek za nadstandardní balení'),
            $price,
            $vatPercent,
            1,
            OrderItem::TYPE_TRANSPORT_FEE,
            null,
            null
        );
    }
}

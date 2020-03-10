<?php

declare(strict_types=1);

namespace App\Model\Order\Item;

use App\Model\Order\Order;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFacade as BaseOrderItemFacade;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Model\Product\ProductRepository $productRepository
 * @property \App\Model\Order\Item\OrderItemFactory $orderItemFactory
 * @method \App\Model\Order\Item\OrderItem addProductToOrder(int $orderId, int $productId)
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Order\OrderRepository $orderRepository, \App\Model\Product\ProductRepository $productRepository, \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForCustomerUser, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation $orderPriceCalculation, \App\Model\Order\Item\OrderItemFactory $orderItemFactory)
 */
class OrderItemFacade extends BaseOrderItemFacade
{
    /**
     * @var \App\Model\Order\OrderRepository
     */
    protected $orderRepository;

    /**
     * @param \App\Model\Order\Order $order
     * @param string $orderItemEan
     * @param int $quantity
     */
    public function setOrderItemPreparedQuantity(Order $order, string $orderItemEan, int $quantity): void
    {
        $orderItems = $this->orderRepository->findOrderItemsByEan($order, $orderItemEan);

        foreach ($orderItems as $orderItem) {
            $orderItem->setPreparedQuantity($quantity);
            $this->em->flush($orderItem);
        }
    }
}

<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Item;

use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFacade as BaseOrderItemFacade;
use Shopsys\ShopBundle\Model\Order\Order;

/**
 * @property \Shopsys\ShopBundle\Model\Product\ProductRepository $productRepository
 * @property \Shopsys\ShopBundle\Model\Order\Item\OrderItemFactory $orderItemFactory
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \Shopsys\ShopBundle\Model\Order\OrderRepository $orderRepository, \Shopsys\ShopBundle\Model\Product\ProductRepository $productRepository, \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser $productPriceCalculationForUser, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation $orderPriceCalculation, \Shopsys\ShopBundle\Model\Order\Item\OrderItemFactory $orderItemFactory)
 * @method \Shopsys\ShopBundle\Model\Order\Item\OrderItem addProductToOrder(int $orderId, int $productId)
 */
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

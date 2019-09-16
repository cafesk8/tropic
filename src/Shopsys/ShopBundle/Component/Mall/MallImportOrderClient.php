<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Mall;

use MPAPI\Entity\Order;
use MPAPI\Services\Orders;
use Shopsys\ShopBundle\Component\Mall\Exception\BadStatusFromFinallyStatusException;
use Shopsys\ShopBundle\Component\Mall\Exception\BadStatusFromOpenStatusException;
use Shopsys\ShopBundle\Component\Mall\Exception\BadStatusFromShippedStatusException;
use Shopsys\ShopBundle\Component\Mall\Exception\BadStatusFromShippingStatusException;

class MallImportOrderClient
{
    /**
     * @var \MPAPI\Services\Orders
     */
    private $ordersService;

    /**
     * @var bool
     */
    private $includeTestOrders;

    /**
     * @param \Shopsys\ShopBundle\Component\Mall\MallClient $mallClient
     * @param bool $includeTestOrders
     */
    public function __construct(MallClient $mallClient, bool $includeTestOrders)
    {
        $this->ordersService = new Orders($mallClient->getClient());
        $this->includeTestOrders = $includeTestOrders;
    }

    /**
     * @return array
     */
    public function getOpenedOrders(): array
    {
        return $this->ordersService->get()->includeTestOrders($this->includeTestOrders)->open();
    }

    /**
     * @param int $orderId
     * @return \MPAPI\Entity\Order
     */
    public function getOrderDetail(int $orderId): Order
    {
        return $this->ordersService->get()->detail($orderId);
    }

    /**
     * @param int $orderMallId
     * @param string $previousStatus
     * @param string $newStatus
     * @param bool $isConfirmed
     * @throws \Exception
     */
    public function changeStatus(int $orderMallId, string $previousStatus, string $newStatus, bool $isConfirmed = true): void
    {
        if ($previousStatus === Order::STATUS_OPEN && in_array($newStatus, [Order::STATUS_OPEN, Order::STATUS_SHIPPING, Order::STATUS_CANCELLED], true) === false) {
            throw new BadStatusFromOpenStatusException();
        }
        if ($previousStatus === Order::STATUS_SHIPPING && in_array($newStatus, [Order::STATUS_SHIPPING, Order::STATUS_SHIPPED, Order::STATUS_CANCELLED], true) === false) {
            throw new BadStatusFromShippingStatusException();
        }
        if ($previousStatus === Order::STATUS_SHIPPED && in_array($newStatus, [Order::STATUS_SHIPPED, Order::STATUS_RETURNED], true) === false) {
            throw new BadStatusFromShippedStatusException();
        }
        if (in_array($previousStatus, [Order::STATUS_RETURNED, Order::STATUS_CANCELLED, Order::STATUS_DELIVERED], true) === true) {
            throw new BadStatusFromFinallyStatusException();
        }

        if ($newStatus === Order::STATUS_DELIVERED) {
            $this->ordersService->put()->status($orderMallId, $newStatus, $isConfirmed, '', new \DateTime());
            return;
        }

        $this->ordersService->put()->status($orderMallId, $newStatus, $isConfirmed);
    }
}

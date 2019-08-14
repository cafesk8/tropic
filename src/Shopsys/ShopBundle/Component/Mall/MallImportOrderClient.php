<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Mall;

use MPAPI\Entity\Order;
use MPAPI\Services\Orders;

class MallImportOrderClient
{
    /**
     * @var \MPAPI\Services\Orders
     */
    private $ordersService;

    /**
     * @param \Shopsys\ShopBundle\Component\Mall\MallClient $mallClient
     */
    public function __construct(MallClient $mallClient)
    {
        $this->ordersService = new Orders($mallClient->getClient());
    }

    /**
     * @return array
     */
    public function getOpenedOrders(): array
    {
        return $this->ordersService->get()->open();
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
     * @param string $orderMallId
     * @param string $newStatus
     * @param bool $isConfirmed
     */
    public function changeStatus(string $orderMallId, string $newStatus, bool $isConfirmed = true): void
    {
        $this->ordersService->put()->status($orderMallId, $newStatus, $isConfirmed);
    }
}

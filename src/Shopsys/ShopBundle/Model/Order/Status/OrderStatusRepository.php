<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Status;

use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusRepository as BaseOrderStatusRepository;

class OrderStatusRepository extends BaseOrderStatusRepository
{
    /**
     * @param string $transferStatus
     * @return \Shopsys\ShopBundle\Model\Order\Status\OrderStatus|null
     */
    public function findByTransferStatus(string $transferStatus): ?OrderStatus
    {
        return $this->getOrderStatusRepository()->findOneBy(['transferStatus' => $transferStatus]);
    }
}

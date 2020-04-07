<?php

declare(strict_types=1);

namespace App\Model\Order\Status;

use Shopsys\FrameworkBundle\Model\Order\Status\Exception\OrderStatusNotFoundException;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusRepository as BaseOrderStatusRepository;

/**
 * @method \App\Model\Order\Status\OrderStatus|null findById(int $orderStatusId)
 * @method \App\Model\Order\Status\OrderStatus getById(int $orderStatusId)
 * @method \App\Model\Order\Status\OrderStatus getDefault()
 * @method \App\Model\Order\Status\OrderStatus[] getAll()
 * @method \App\Model\Order\Status\OrderStatus[] getAllIndexedById()
 * @method \App\Model\Order\Status\OrderStatus[] getAllExceptId(int $orderStatusId)
 * @method replaceOrderStatus(\App\Model\Order\Status\OrderStatus $oldOrderStatus, \App\Model\Order\Status\OrderStatus $newOrderStatus)
 */
class OrderStatusRepository extends BaseOrderStatusRepository
{
    /**
     * @param string $transferStatus
     * @return \App\Model\Order\Status\OrderStatus|null
     */
    public function findByTransferStatus(string $transferStatus): ?OrderStatus
    {
        return $this->getOrderStatusRepository()->findOneBy(['transferStatus' => $transferStatus]);
    }

    /**
     * @param int $type
     * @return \App\Model\Order\Status\OrderStatus
     */
    public function getByType(int $type): OrderStatus
    {
        $orderStatus = $this->getOrderStatusRepository()->findOneBy(['type' => $type]);
        if ($orderStatus === null) {
            throw new OrderStatusNotFoundException(sprintf('Order status for type `%s` not found', $type));
        }

        return $orderStatus;
    }
}

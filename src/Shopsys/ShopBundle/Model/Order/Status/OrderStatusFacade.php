<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Status;

use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusFacade as BaseOrderStatusFacade;

class OrderStatusFacade extends BaseOrderStatusFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Order\Status\OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @param string $transferId
     * @return \Shopsys\ShopBundle\Model\Order\Status\OrderStatus|null
     */
    public function findByTransferStatus(string $transferId): ?OrderStatus
    {
        return $this->orderStatusRepository->findByTransferStatus($transferId);
    }
}

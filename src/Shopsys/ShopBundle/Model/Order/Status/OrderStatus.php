<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Status;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatus as BaseOrderStatus;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusData;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusTranslation;

/**
 * @ORM\Table(name="order_statuses")
 * @ORM\Entity
 *
 * @method OrderStatusTranslation translation(?string $locale = null)
 */
class OrderStatus extends BaseOrderStatus
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $transferStatus;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Status\OrderStatusData $orderStatusData
     * @param $type
     */
    public function __construct(OrderStatusData $orderStatusData, $type)
    {
        parent::__construct($orderStatusData, $type);
        $this->transferStatus = $orderStatusData->transferStatus;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Status\OrderStatusData $orderStatusData
     */
    public function edit(OrderStatusData $orderStatusData): void
    {
        parent::edit($orderStatusData);
        $this->transferStatus = $orderStatusData->transferStatus;
    }

    /**
     * @return string|null
     */
    public function getTransferStatus(): ?string
    {
        return $this->transferStatus;
    }
}

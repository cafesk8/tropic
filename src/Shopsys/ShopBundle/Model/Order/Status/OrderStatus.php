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
    public const SMS_ALERT_5_DAY_BEFORE = 'smsAlert5dayBefore';
    public const SMS_ALERT_2_DAY_BEFORE = 'smsAlert2dayBefore';

    public const TYPE_ALMOST_READY = 5;
    public const TYPE_ALMOST_READY_STORE = 6;
    public const TYPE_READY = 7;
    public const TYPE_READY_STORE = 8;
    public const TYPE_RETURNED = 9;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $transferStatus;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $smsAlertType;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $checkOrderReadyStatus;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Status\OrderStatusData $orderStatusData
     * @param $type
     */
    public function __construct(OrderStatusData $orderStatusData, $type)
    {
        parent::__construct($orderStatusData, $type);
        $this->transferStatus = $orderStatusData->transferStatus;
        $this->smsAlertType = $orderStatusData->smsAlertType;
        $this->checkOrderReadyStatus = $orderStatusData->checkOrderReadyStatus;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Status\OrderStatusData $orderStatusData
     */
    public function edit(OrderStatusData $orderStatusData): void
    {
        parent::edit($orderStatusData);
        $this->transferStatus = $orderStatusData->transferStatus;
        $this->smsAlertType = $orderStatusData->smsAlertType;
    }

    /**
     * @return string|null
     */
    public function getTransferStatus(): ?string
    {
        return $this->transferStatus;
    }

    /**
     * @return string|null
     */
    public function getSmsAlertType(): ?string
    {
        return $this->smsAlertType;
    }

    /**
     * @return bool
     */
    public function isCheckOrderReadyStatus(): bool
    {
        return $this->checkOrderReadyStatus;
    }

    /**
     * @return bool
     */
    public function isOrderStatusReady(): bool
    {
        return in_array($this->getType(), [self::TYPE_READY, self::TYPE_READY_STORE], true);
    }

    /**
     * @inheritDoc
     */
    protected function setType($type): void
    {
        if (in_array($type, [
            self::TYPE_ALMOST_READY,
            self::TYPE_ALMOST_READY_STORE,
            self::TYPE_READY,
            self::TYPE_READY_STORE,
            self::TYPE_RETURNED,
        ], true)) {
            $this->type = $type;
        } else {
            parent::setType($type);
        }
    }
}

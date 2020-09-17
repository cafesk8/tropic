<?php

declare(strict_types=1);

namespace App\Model\Order\Status;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatus as BaseOrderStatus;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusData;

/**
 * @ORM\Table(name="order_statuses")
 * @ORM\Entity
 *
 * @method \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusTranslation translation(?string $locale = null)
 * @method setTranslations(\App\Model\Order\Status\OrderStatusData $orderStatusData)
 */
class OrderStatus extends BaseOrderStatus
{
    public const TYPE_CUSTOMER_DID_NOT_PICK_UP = 5;
    public const TYPE_PAID = 6;

    public const SMS_ALERT_5_DAY_BEFORE = 'smsAlert5dayBefore';
    public const SMS_ALERT_2_DAY_BEFORE = 'smsAlert2dayBefore';

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
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $activatesGiftCertificates;

    /**
     * @param \App\Model\Order\Status\OrderStatusData $orderStatusData
     * @param int $type
     */
    public function __construct(OrderStatusData $orderStatusData, $type)
    {
        parent::__construct($orderStatusData, $type);
        $this->transferStatus = $orderStatusData->transferStatus;
        $this->smsAlertType = $orderStatusData->smsAlertType;
        $this->checkOrderReadyStatus = $orderStatusData->checkOrderReadyStatus;
        $this->activatesGiftCertificates = $orderStatusData->activatesGiftCertificates;
    }

    /**
     * @param \App\Model\Order\Status\OrderStatusData $orderStatusData
     */
    public function edit(OrderStatusData $orderStatusData): void
    {
        parent::edit($orderStatusData);
        $this->transferStatus = $orderStatusData->transferStatus;
        $this->smsAlertType = $orderStatusData->smsAlertType;
        $this->activatesGiftCertificates = $orderStatusData->activatesGiftCertificates;
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
    public function isCanceled(): bool
    {
        return $this->getType() === self::TYPE_CANCELED;
    }

    /**
     * @return bool
     */
    public function activatesGiftCertificates(): bool
    {
        return $this->activatesGiftCertificates;
    }
}

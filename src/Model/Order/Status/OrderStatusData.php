<?php

declare(strict_types=1);

namespace App\Model\Order\Status;

use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusData as BaseOrderStatusData;

class OrderStatusData extends BaseOrderStatusData
{
    /**
     * @var string|null
     */
    public $transferStatus;

    /**
     * @var string|null
     */
    public $smsAlertType;

    /**
     * @var bool|null
     */
    public $checkOrderReadyStatus;

    /**
     * @var bool
     */
    public $activatesGiftCertificates;
}

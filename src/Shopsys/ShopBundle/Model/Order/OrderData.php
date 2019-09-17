<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use DateTime;
use Shopsys\FrameworkBundle\Model\Order\OrderData as BaseOrderData;
use Shopsys\ShopBundle\Model\Transport\Transport;

class OrderData extends BaseOrderData
{
    /**
     * @var int|null
     */
    public $goPayId;

    /**
     * @var string|null
     */
    public $goPayStatus;

    /**
     * @var string|null
     */
    public $payPalId;

    /**
     * @var string|null
     */
    public $payPalStatus;

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace|null
     */
    public $pickupPlace;

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\Transport|null
     */
    public $transport;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\Store|null
     */
    public $store;

    /**
     * @var \DateTime
     */
    public $updatedAt;

    /**
     * @var \DateTime
     */
    public $statusCheckedAt;

    /**
     * @var string
     */
    public $exportStatus = Order::EXPORT_NOT_YET;

    /**
     * @var \DateTime|null
     */
    public $exportedAt;

    /**
     * @var string|null
     */
    public $mallOrderId;

    /**
     * @var string|null
     */
    public $mallStatus;

    /**
     * @var bool
     */
    public $memberOfBushmanClub;

    /**
     * @var string|null
     */
    public $personalTakeType;

    public function __construct()
    {
        parent::__construct();
        $this->updatedAt = new DateTime();
        $this->statusCheckedAt = new DateTime();
        $this->memberOfBushmanClub = false;
        $this->personalTakeType = Transport::PERSONAL_TAKE_TYPE_NONE;
    }

    /**
     * @var string|null
     */
    public $gtmCoupon;
}

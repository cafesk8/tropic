<?php

namespace Shopsys\ShopBundle\Model\Order;

use Shopsys\FrameworkBundle\Model\Order\OrderData as BaseOrderData;

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

    public function __construct()
    {
        parent::__construct();
    }
}

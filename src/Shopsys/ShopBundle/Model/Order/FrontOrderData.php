<?php

namespace Shopsys\ShopBundle\Model\Order;

use Shopsys\FrameworkBundle\Model\Order\FrontOrderData as BaseFrontOrderData;

class FrontOrderData extends BaseFrontOrderData
{
    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwift
     */
    public $goPayBankSwift;

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace|null
     */
    public $pickupPlace;
}

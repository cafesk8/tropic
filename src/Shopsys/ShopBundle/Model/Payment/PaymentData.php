<?php

namespace Shopsys\ShopBundle\Model\Payment;

use Shopsys\FrameworkBundle\Model\Payment\PaymentData as BasePaymentData;

class PaymentData extends BasePaymentData
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod|null
     */
    public $goPayPaymentMethod;

    public function __construct()
    {
        parent::__construct();

        $this->type = Payment::TYPE_BASIC;
    }
}

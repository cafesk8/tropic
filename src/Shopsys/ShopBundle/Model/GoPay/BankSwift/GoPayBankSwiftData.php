<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\GoPay\BankSwift;

class GoPayBankSwiftData
{
    /**
     * @var string|null
     */
    public $swift;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod|null
     */
    public $goPayPaymentMethod;

    /**
     * @var string|null
     */
    public $name;

    /**
     * @var string|null
     */
    public $imageNormalUrl;

    /**
     * @var string|null
     */
    public $imageLargeUrl;

    /**
     * @var bool|null
     */
    public $isOnline;
}

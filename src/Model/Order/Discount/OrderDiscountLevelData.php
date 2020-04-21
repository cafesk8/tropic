<?php

declare(strict_types=1);

namespace App\Model\Order\Discount;

class OrderDiscountLevelData
{
    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public $priceLevelWithVat;

    /**
     * @var int
     */
    public $domainId;

    /**
     * @var int
     */
    public $discountPercent;
}

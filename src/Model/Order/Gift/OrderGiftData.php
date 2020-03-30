<?php

declare(strict_types=1);

namespace App\Model\Order\Gift;

class OrderGiftData
{
    /**
     * @var \App\Model\Product\Product[]
     */
    public $products;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public $priceLevelWithVat;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var int
     */
    public $domainId;
}

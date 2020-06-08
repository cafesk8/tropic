<?php

declare(strict_types=1);

namespace App\Model\WatchDog;

use DateTime;

class WatchDogData
{
    /**
     * @var \DateTime
     */
    public $createdAt;

    /**
     * @var \App\Model\Product\Product
     */
    public $product;

    /**
     * @var string
     */
    public $email;

    /**
     * @var bool
     */
    public $availabilityWatcher;

    /**
     * @var bool
     */
    public $priceWatcher;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public $originalPrice;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public $targetedDiscount;

    /**
     * @var \App\Model\Pricing\Group\PricingGroup
     */
    public $pricingGroup;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->availabilityWatcher = true;
        $this->priceWatcher = true;
        $this->targetedDiscount = null;
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Pricing\Group;

use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupData as BasePricingGroupData;

class PricingGroupData extends BasePricingGroupData
{
    /**
     * @var string|null
     */
    public $internalId;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public $minimalPrice;

    /**
     * @var float
     */
    public $discount;

    /**
     * @var bool
     */
    public $calculatedFromDefault;

    /**
     * @var string|null
     */
    public $pohodaIdent;

    public function __construct()
    {
        $this->discount = 0;
        $this->calculatedFromDefault = false;
        $this->minimalPrice = null;
    }
}

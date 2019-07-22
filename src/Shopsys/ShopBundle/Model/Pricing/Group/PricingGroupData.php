<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Pricing\Group;

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
     * @var float|null
     */
    public $discount;
}

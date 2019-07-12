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
}

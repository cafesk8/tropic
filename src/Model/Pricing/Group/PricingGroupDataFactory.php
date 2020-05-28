<?php

declare(strict_types=1);

namespace App\Model\Pricing\Group;

use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupData as BasePricingGroupData;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupDataFactory as BasePricingGroupDataFactory;

/**
 * @method \App\Model\Pricing\Group\PricingGroupData createInstance()
 */
class PricingGroupDataFactory extends BasePricingGroupDataFactory
{
    /**
     * @return \App\Model\Pricing\Group\PricingGroupData
     */
    public function create(): BasePricingGroupData
    {
        return new PricingGroupData();
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Pricing\Group\PricingGroupData
     */
    public function createFromPricingGroup(PricingGroup $pricingGroup): BasePricingGroupData
    {
        $pricingGroupData = $this->create();
        $this->fillFromPricingGroup($pricingGroupData, $pricingGroup);

        return $pricingGroupData;
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroupData $pricingGroupData
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     */
    public function fillFromPricingGroup(BasePricingGroupData $pricingGroupData, PricingGroup $pricingGroup): void
    {
        parent::fillFromPricingGroup($pricingGroupData, $pricingGroup);
        $pricingGroupData->internalId = $pricingGroup->getInternalId();
        $pricingGroupData->minimalPrice = $pricingGroup->getMinimalPrice();
        $pricingGroupData->discount = $pricingGroup->getDiscount();
        $pricingGroupData->calculatedFromDefault = $pricingGroup->isCalculatedFromDefault();
        $pricingGroupData->pohodaIdent = $pricingGroup->getPohodaIdent();
    }
}

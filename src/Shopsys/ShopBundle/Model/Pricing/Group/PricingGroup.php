<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Pricing\Group;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup as BasePricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupData;

/**
 * @ORM\Table(name="pricing_groups")
 * @ORM\Entity
 */
class PricingGroup extends BasePricingGroup
{
    public const PRICING_GROUP_ORDINARY_CUSTOMER = 'ordinary_customer';
    public const PRICING_GROUP_ADEPT = 'adept';
    public const PRICING_GROUP_CLASSIC = 'classic';
    public const PRICING_GROUP_REAL_BUSHMAN = 'real_bushman';
    public const PRICING_GROUP_TEMPORARY_SEVEN_PERCENT_GROUP_DOMAIN = 'temporary_seven_percent';

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $internalId;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     *
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private $minimalPrice;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $discount;

    /**
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupData $pricingGroupData
     * @param $domainId
     */
    public function __construct(PricingGroupData $pricingGroupData, $domainId)
    {
        parent::__construct($pricingGroupData, $domainId);
        $this->internalId = $pricingGroupData->internalId;
        $this->minimalPrice = $pricingGroupData->minimalPrice;
        $this->discount = $pricingGroupData->discount;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupData $pricingGroupData
     */
    public function edit(PricingGroupData $pricingGroupData): void
    {
        parent::edit($pricingGroupData);
        $this->internalId = $pricingGroupData->internalId;
        $this->minimalPrice = $pricingGroupData->minimalPrice;
        $this->discount = $pricingGroupData->discount;
    }

    /**
     * @return string|null
     */
    public function getInternalId(): ?string
    {
        return $this->internalId;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getMinimalPrice(): ?Money
    {
        return $this->minimalPrice;
    }

    /**
     * @return float|null
     */
    public function getDiscount(): ?float
    {
        return $this->discount;
    }
}

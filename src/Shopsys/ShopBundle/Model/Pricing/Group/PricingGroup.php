<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Pricing\Group;

use Doctrine\ORM\Mapping as ORM;
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

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $internalId;

    /**
     * @return string|null
     */
    public function getInternalId(): ?string
    {
        return $this->internalId;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupData $pricingGroupData
     * @param $domainId
     */
    public function __construct(PricingGroupData $pricingGroupData, $domainId)
    {
        parent::__construct($pricingGroupData, $domainId);
        $this->internalId = $pricingGroupData->internalId;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupData $pricingGroupData
     */
    public function edit(PricingGroupData $pricingGroupData): void
    {
        parent::edit($pricingGroupData);
        $this->internalId = $pricingGroupData->internalId;
    }
}

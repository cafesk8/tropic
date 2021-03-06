<?php

declare(strict_types=1);

namespace App\Model\Pricing\Group;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup as BasePricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupData as BasePricingGroupData;

/**
 * @ORM\Table(name="pricing_groups")
 * @ORM\Entity
 */
class PricingGroup extends BasePricingGroup
{
    public const PRICING_GROUP_ORDINARY_CUSTOMER = 'ordinary_customer';
    public const PRICING_GROUP_REGISTERED_CUSTOMER = 'registered_customer';
    public const PRICING_GROUP_PURCHASE_PRICE = 'purchase_price';
    public const PRICING_GROUP_STANDARD_PRICE = 'standard_price';
    public const PRICING_GROUP_SALE_PRICE = 'sale_price';

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
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $discount;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $calculatedFromDefault;

    /**
     * in Pohoda, this is "SkCeny.IDS"
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $pohodaIdent = null;

    /**
     * in Pohoda, this is "SkCeny.ID"
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $pohodaId = null;

    /**
     * @param \App\Model\Pricing\Group\PricingGroupData $pricingGroupData
     * @param int $domainId
     */
    public function __construct(BasePricingGroupData $pricingGroupData, $domainId)
    {
        parent::__construct($pricingGroupData, $domainId);
        $this->fillCommonProperties($pricingGroupData);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroupData $pricingGroupData
     */
    public function edit(BasePricingGroupData $pricingGroupData): void
    {
        parent::edit($pricingGroupData);
        $this->fillCommonProperties($pricingGroupData);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroupData $pricingGroupData
     */
    private function fillCommonProperties(PricingGroupData $pricingGroupData): void
    {
        $this->internalId = $pricingGroupData->internalId;
        $this->minimalPrice = $pricingGroupData->minimalPrice;
        $this->discount = $pricingGroupData->discount;
        $this->calculatedFromDefault = $pricingGroupData->calculatedFromDefault;
        $this->pohodaIdent = $pricingGroupData->pohodaIdent;
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
     * @return float
     */
    public function getDiscount(): float
    {
        return $this->discount;
    }

    /**
     * @return float
     */
    public function getDiscountCoefficient(): float
    {
        return (100 - $this->discount) / 100;
    }

    /**
     * @return bool
     */
    public function isCalculatedFromDefault(): bool
    {
        return $this->calculatedFromDefault;
    }

    /**
     * @return bool
     */
    public function isOrdinaryCustomerPricingGroup(): bool
    {
        return $this->internalId === self::PRICING_GROUP_ORDINARY_CUSTOMER;
    }

    /**
     * @return bool
     */
    public function isRegisteredCustomerPricingGroup(): bool
    {
        return $this->internalId === self::PRICING_GROUP_REGISTERED_CUSTOMER;
    }

    /**
     * @return bool
     */
    public function isStandardPricePricingGroup(): bool
    {
        return $this->internalId === self::PRICING_GROUP_STANDARD_PRICE;
    }

    /**
     * @return bool
     */
    public function isSalePricePricingGroup(): bool
    {
        return $this->internalId === self::PRICING_GROUP_SALE_PRICE;
    }

    /**
     * @return null|string
     */
    public function getPohodaIdent(): ?string
    {
        return $this->pohodaIdent;
    }

    /**
     * @return int|null
     */
    public function getPohodaId(): ?int
    {
        return $this->pohodaId;
    }
}

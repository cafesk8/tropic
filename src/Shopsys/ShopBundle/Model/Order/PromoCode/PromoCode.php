<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode as BasePromoCode;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeData as BasePromoCodeData;

/**
 * @ORM\Table(name="promo_codes")
 * @ORM\Entity
 */
class PromoCode extends BasePromoCode
{
    public const MAX_CODES_GENERATE = 99999;
    public const MASS_GENERATED_CODE_LENGTH = 6;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $domainId;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $unlimited;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $usageLimit;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $numberOfUses;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $validFrom;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $validTo;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money
     *
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private $minOrderValue;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $massGenerate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $prefix;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData $promoCodeData
     */
    public function __construct(BasePromoCodeData $promoCodeData)
    {
        parent::__construct($promoCodeData);

        $this->domainId = $promoCodeData->domainId;
        $this->unlimited = $promoCodeData->unlimited;
        $this->usageLimit = $promoCodeData->usageLimit;
        $this->numberOfUses = $promoCodeData->numberOfUses;
        $this->validFrom = $promoCodeData->validFrom;
        $this->validTo = $promoCodeData->validTo;
        $this->minOrderValue = $promoCodeData->minOrderValue;
        $this->massGenerate = $promoCodeData->massGenerate;
        $this->prefix = $promoCodeData->prefix;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData $promoCodeData
     */
    public function edit(BasePromoCodeData $promoCodeData): void
    {
        parent::edit($promoCodeData);

        $this->unlimited = $promoCodeData->unlimited;
        $this->usageLimit = $promoCodeData->usageLimit;
        $this->numberOfUses = $promoCodeData->numberOfUses;
        $this->validFrom = $promoCodeData->validFrom;
        $this->validTo = $promoCodeData->validTo;
        $this->minOrderValue = $promoCodeData->minOrderValue;
        $this->massGenerate = $promoCodeData->massGenerate;
        $this->prefix = $promoCodeData->prefix;
    }

    /**
     * @return int
     */
    public function getDomainId(): int
    {
        return $this->domainId;
    }

    /**
     * @return bool
     */
    public function isUnlimited(): bool
    {
        return $this->unlimited;
    }

    /**
     * @return int|null
     */
    public function getUsageLimit(): ?int
    {
        return $this->usageLimit;
    }

    /**
     * @return int
     */
    public function getNumberOfUses(): int
    {
        return $this->numberOfUses;
    }

    public function addUsage(): void
    {
        $this->numberOfUses++;
    }

    /**
     * @return bool
     */
    public function hasRemainingUses(): bool
    {
        if ($this->isUnlimited() === true) {
            return true;
        }

        if ($this->usageLimit !== null) {
            return ($this->usageLimit - $this->numberOfUses) > 0;
        }

        return true;
    }

    /**
     * @return \DateTime|null
     */
    public function getValidFrom(): ?DateTime
    {
        return $this->validFrom;
    }

    /**
     * @return \DateTime|null
     */
    public function getValidTo(): ?DateTime
    {
        return $this->validTo;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getMinOrderValue(): ?Money
    {
        return $this->minOrderValue;
    }

    /**
     * @return bool
     */
    public function isMassGenerated(): bool
    {
        return $this->massGenerate;
    }

    /**
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }
}

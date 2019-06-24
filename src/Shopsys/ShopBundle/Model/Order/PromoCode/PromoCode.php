<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode as BasePromoCode;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeData as BasePromoCodeData;

/**
 * @ORM\Table(name="promo_codes")
 * @ORM\Entity
 */
class PromoCode extends BasePromoCode
{
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
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData $promoCodeData
     */
    public function __construct(BasePromoCodeData $promoCodeData)
    {
        parent::__construct($promoCodeData);

        $this->unlimited = $promoCodeData->unlimited;
        $this->usageLimit = $promoCodeData->usageLimit;
        $this->numberOfUses = $promoCodeData->numberOfUses;
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
}

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
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData $promoCodeData
     */
    public function __construct(BasePromoCodeData $promoCodeData)
    {
        parent::__construct($promoCodeData);

        $this->unlimited = $promoCodeData->unlimited;
        $this->usageLimit = $promoCodeData->usageLimit;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData $promoCodeData
     */
    public function edit(BasePromoCodeData $promoCodeData): void
    {
        parent::edit($promoCodeData);

        $this->unlimited = $promoCodeData->unlimited;
        $this->usageLimit = $promoCodeData->usageLimit;
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
}

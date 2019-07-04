<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeData as BasePromoCodeData;

class PromoCodeData extends BasePromoCodeData
{
    /**
     * @var int|null
     */
    public $domainId;

    /**
     * @var bool|null
     */
    public $unlimited;

    /**
     * @var int|null
     */
    public $usageLimit;

    /**
     * @var int|null
     */
    public $numberOfUses;

    /**
     * @var \DateTime|null
     */
    public $validFrom;

    /**
     * @var \DateTime|null
     */
    public $validTo;
}

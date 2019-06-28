<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeData as BasePromoCodeData;

class PromoCodeData extends BasePromoCodeData
{
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
}

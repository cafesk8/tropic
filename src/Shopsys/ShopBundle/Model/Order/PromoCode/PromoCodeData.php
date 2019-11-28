<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeData as BasePromoCodeData;

class PromoCodeData extends BasePromoCodeData
{
    public const TYPE_PROMO_CODE = 'promoCode';

    public const TYPE_CERTIFICATE = 'certificate';

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

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public $minOrderValue;

    /**
     * @var bool|null
     */
    public $massGenerate;

    /**
     * @var string|null
     */
    public $prefix;

    /**
     * @var int|null
     */
    public $quantity;

    /**
     * @var bool
     */
    public $useNominalDiscount;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public $nominalDiscount;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string|null
     */
    public $certificateValue;

    /**
     * @var string|null
     */
    public $certificateSku;

    /**
     * @var string|null
     */
    public $usageType;

    /**
     * @var string|null
     */
    public $userType;
}

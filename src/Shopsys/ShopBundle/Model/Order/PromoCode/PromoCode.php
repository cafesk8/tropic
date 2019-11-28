<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode as BasePromoCode;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeData as BasePromoCodeData;
use Shopsys\ShopBundle\Model\Order\PromoCode\Exception\InvalidPromoCodeUsageTypeException;

/**
 * @ORM\Table(name="promo_codes")
 * @ORM\Entity
 */
class PromoCode extends BasePromoCode
{
    public const MAX_CODES_GENERATE = 9999;
    public const MASS_GENERATED_CODE_LENGTH = 6;

    public const USAGE_TYPE_ALL = 'all';
    public const USAGE_TYPE_WITH_ACTION_PRICE = 'withActionPrice';
    public const USAGE_TYPE_NO_ACTION_PRICE = 'noActionPrice';

    public const USER_TYPE_ALL = 'all_users';
    public const USER_TYPE_LOGGED = 'logged_users';
    public const USER_TYPE_BUSHMAN_CLUB_MEMBERS = 'bushman_club_member_users';

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
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
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
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $useNominalDiscount;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     *
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private $nominalDiscount;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $type;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     *
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private $certificateValue;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $certificateSku;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $usageType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $userType;

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
        $this->nominalDiscount = $promoCodeData->nominalDiscount;
        $this->useNominalDiscount = $promoCodeData->useNominalDiscount;
        $this->type = $promoCodeData->type;
        $this->certificateValue = $promoCodeData->certificateValue;
        $this->certificateSku = $promoCodeData->certificateSku;
        $this->setUsageType($promoCodeData->usageType);
        $this->setUserType($promoCodeData->userType);
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
        $this->nominalDiscount = $promoCodeData->nominalDiscount;
        $this->useNominalDiscount = $promoCodeData->useNominalDiscount;
        $this->type = $promoCodeData->type;
        $this->certificateValue = $promoCodeData->certificateValue;
        $this->certificateSku = $promoCodeData->certificateSku;
        $this->setUsageType($promoCodeData->usageType);
        $this->setUserType($promoCodeData->userType);
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

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getNominalDiscount(): ?Money
    {
        return $this->nominalDiscount;
    }

    /**
     * @return bool
     */
    public function isUseNominalDiscount(): bool
    {
        return $this->useNominalDiscount;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getCertificateValue(): ?Money
    {
        return $this->certificateValue;
    }

    /**
     * @return string|null
     */
    public function getCertificateSku(): ?string
    {
        return $this->certificateSku;
    }

    /**
     * @return string
     */
    public function getUsageType(): string
    {
        return $this->usageType;
    }

    /**
     * @param string $usageType
     */
    public function setUsageType(string $usageType): void
    {
        if (in_array($usageType, [self::USAGE_TYPE_ALL, self::USAGE_TYPE_NO_ACTION_PRICE, self::USAGE_TYPE_WITH_ACTION_PRICE], true) === false) {
            throw new InvalidPromoCodeUsageTypeException(sprintf('Invalid promo code use type `%s`', $usageType));
        }
        $this->usageType = $usageType;
    }

    /**
     * @return string
     */
    public function getUserType(): string
    {
        return $this->userType;
    }

    /**
     * @param string $userType
     */
    public function setUserType(string $userType): void
    {
        if (in_array($userType, [self::USER_TYPE_ALL, self::USER_TYPE_LOGGED, self::USER_TYPE_BUSHMAN_CLUB_MEMBERS], true) === false) {
            throw new InvalidPromoCodeUsageTypeException(sprintf('Invalid promo code user type `%s`', $userType));
        }

        $this->userType = $userType;
    }

    /**
     * @return bool
     */
    public function isUserTypeAll(): bool
    {
        return $this->userType === self::USER_TYPE_ALL;
    }

    /**
     * @return bool
     */
    public function isUserTypeLogged(): bool
    {
        return $this->userType === self::USER_TYPE_LOGGED;
    }

    /**
     * @return bool
     */
    public function isUserTypeBushmanClubMembers(): bool
    {
        return $this->userType === self::USER_TYPE_BUSHMAN_CLUB_MEMBERS;
    }
}

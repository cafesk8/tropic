<?php

declare(strict_types=1);

namespace App\Model\Order\PromoCode;

use App\Model\Order\PromoCode\Exception\InvalidPromoCodeUsageTypeException;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode as BasePromoCode;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeData as BasePromoCodeData;

/**
 * @ORM\Table(
 *     name="promo_codes",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="unique_code_on_domain", columns={"code", "domain_id"})
 *     }
 * )
 * @ORM\Entity
 */
class PromoCode extends BasePromoCode
{
    public const MAX_CODES_GENERATE = 9999;
    public const MASS_GENERATED_CODE_LENGTH = 16;

    public const USER_TYPE_ALL = 'all_users';
    public const USER_TYPE_LOGGED = 'logged_users';

    public const LIMIT_TYPE_ALL = 'all';
    public const LIMIT_TYPE_BRANDS = 'brands';
    public const LIMIT_TYPE_CATEGORIES = 'categories';
    public const LIMIT_TYPE_PRODUCTS = 'products';

    /**
     * @ORM\Column(type="text", unique=false)
     */
    protected $code;

    /**
     * @ORM\Column(type="integer")
     */
    private int $domainId;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $unlimited;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $usageLimit;

    /**
     * @ORM\Column(type="integer")
     */
    private int $numberOfUses;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $validFrom;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $validTo;

    /**
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private ?Money $minOrderValue;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $massGenerate;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $prefix;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $useNominalDiscount;

    /**
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private ?Money $nominalDiscount;

    /**
     * @ORM\Column(type="string")
     */
    private string $type;

    /**
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private ?Money $certificateValue;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $certificateSku;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $userType;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $limitType;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\App\Model\Order\PromoCode\PromoCodeLimit[]
     *
     * @ORM\OneToMany(targetEntity="App\Model\Order\PromoCode\PromoCodeLimit", mappedBy="promoCode", cascade={"remove"})
     */
    private Collection $limits;

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeData $promoCodeData
     */
    public function __construct(BasePromoCodeData $promoCodeData)
    {
        parent::__construct($promoCodeData);
        $this->domainId = $promoCodeData->domainId;
        $this->limits = new ArrayCollection();
        $this->fillCommonFields($promoCodeData);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeData $promoCodeData
     */
    public function edit(BasePromoCodeData $promoCodeData): void
    {
        parent::edit($promoCodeData);
        $this->fillCommonFields($promoCodeData);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeData $promoCodeData
     */
    private function fillCommonFields(PromoCodeData $promoCodeData): void
    {
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
        $this->setUserType($promoCodeData->userType);
        $this->setLimitType($promoCodeData->limitType);

        foreach ($promoCodeData->limits as $limit) {
            $this->limits->add($limit);
        }
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
    public function getUserType(): string
    {
        return $this->userType;
    }

    /**
     * @param string $userType
     */
    public function setUserType(string $userType): void
    {
        if (in_array($userType, [self::USER_TYPE_ALL, self::USER_TYPE_LOGGED], true) === false) {
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
    public function isCombinable(): bool
    {
        return $this->type === PromoCodeData::TYPE_CERTIFICATE;
    }

    /**
     * @return string
     */
    public function getLimitType(): string
    {
        return $this->limitType;
    }

    /**
     * @param string $limitType
     */
    public function setLimitType(string $limitType): void
    {
        if (in_array($limitType, [self::LIMIT_TYPE_ALL, self::LIMIT_TYPE_BRANDS, self::LIMIT_TYPE_CATEGORIES, self::LIMIT_TYPE_PRODUCTS], true)) {
            $this->limitType = $limitType;
        } else {
            $this->limitType = self::LIMIT_TYPE_ALL;
        }
    }

    /**
     * @return \App\Model\Order\PromoCode\PromoCodeLimit[]
     */
    public function getLimits(): array
    {
        return $this->limits->toArray();
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->usageLimit !== 0;
    }

    /**
     * @return bool
     */
    public function isTypeGiftCertificate(): bool
    {
        return $this->type === PromoCodeData::TYPE_CERTIFICATE;
    }

    /**
     * @return bool
     */
    public function isTypePromoCode(): bool
    {
        return $this->type === PromoCodeData::TYPE_PROMO_CODE;
    }
}

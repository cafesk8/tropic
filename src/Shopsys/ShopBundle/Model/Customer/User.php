<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddress;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress;
use Shopsys\FrameworkBundle\Model\Customer\User as BaseUser;
use Shopsys\FrameworkBundle\Model\Customer\UserData as BaseUserData;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\ShopBundle\Model\Customer\Exception\UnsupportedCustomerExportStatusException;
use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * @ORM\Table(
 *     name="users",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="email_domain", columns={"email", "domain_id", "ean"})
 *     },
 *     indexes={
 *         @ORM\Index(columns={"email"})
 *     }
 * )
 * @ORM\Entity
 */
class User extends BaseUser
{
    public const EXPORT_SUCCESS = 'export_success';
    public const EXPORT_NOT_YET = 'export_not_yet';
    public const EXPORT_ERROR = 'export_error';

    /**
     * @ORM\Column(type="string", length=60)
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string", length=30)
     */
    protected $lastName;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $email;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $transferId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true, length=13)
     */
    private $ean;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $memberOfBushmanClub;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $exportStatus;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $exportedAt;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan",
     *     mappedBy="customer",
     *     cascade={"remove"},
     *     orphanRemoval=true
     * )
     */
    private $userTransferIdAndEan;

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\UserData $userData
     * @param \Shopsys\FrameworkBundle\Model\Customer\BillingAddress $billingAddress
     * @param \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress|null $deliveryAddress
     * @param \Shopsys\ShopBundle\Model\Customer\User|null $userByEmail
     */
    public function __construct(
        BaseUserData $userData,
        BillingAddress $billingAddress,
        ?DeliveryAddress $deliveryAddress,
        ?BaseUser $userByEmail
    ) {
        parent::__construct($userData, $billingAddress, $deliveryAddress, $userByEmail);

        $this->transferId = $userData->transferId;
        $this->ean = $userData->ean;
        $this->memberOfBushmanClub = $userData->memberOfBushmanClub;
        $this->exportStatus = $userData->exportStatus;
        $this->userTransferIdAndEan = new ArrayCollection();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\UserData $userData
     * @param \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface $encoderFactory
     */
    public function edit(BaseUserData $userData, EncoderFactoryInterface $encoderFactory)
    {
        parent::edit($userData, $encoderFactory);
        $this->ean = $userData->ean;
        $this->memberOfBushmanClub = $userData->memberOfBushmanClub;
    }

    /**
     * @return string|null
     */
    public function getEan(): ?string
    {
        return $this->ean;
    }

    /**
     * @return string|null
     */
    public function getTransferId(): ?string
    {
        return $this->transferId;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     */
    public function setPricingGroup(PricingGroup $pricingGroup): void
    {
        $this->pricingGroup = $pricingGroup;
    }

    /**
     * @param string $transferId
     */
    public function setTransferId(string $transferId): void
    {
        $this->transferId = $transferId;
    }

    /**
     * @param \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface $encoderFactory
     * @param string $password
     */
    public function changePasswordByMigration(EncoderFactoryInterface $encoderFactory, $password): void
    {
        $encoder = $encoderFactory->getEncoder($this);

        if ($encoder instanceof BushmanCustomPasswordEncoder) {
            $passwordHash = $encoder->getHashOfMigratedPassword($password, null);
            $this->password = $passwordHash;
            $this->resetPasswordHash = null;
            $this->resetPasswordHashValidThrough = null;
            return;
        }

        parent::changePassword($encoderFactory, $password);
    }

    /**
     * @return bool
     */
    public function isMemberOfBushmanClub(): bool
    {
        return $this->memberOfBushmanClub;
    }

    /**
     * @return string
     */
    public function getExportStatus(): string
    {
        return $this->exportStatus;
    }

    /**
     * @param string $exportStatus
     * @return string
     */
    private function setExportStatus(string $exportStatus): void
    {
        if (in_array($exportStatus, [self::EXPORT_SUCCESS, self::EXPORT_NOT_YET, self::EXPORT_ERROR], true) === false) {
            throw new UnsupportedCustomerExportStatusException(
                sprintf('Export status `%s` is not supported.', $exportStatus)
            );
        }

        $this->exportStatus = $exportStatus;
    }

    public function markAsExported(): void
    {
        $this->setExportStatus(self::EXPORT_SUCCESS);
        $this->exportedAt = new DateTime();
    }

    public function markAsFailedExported(): void
    {
        $this->setExportStatus(self::EXPORT_ERROR);
        $this->exportedAt = new DateTime();
    }

    /**
     * @return \DateTime|null
     */
    public function getExportedAt(): ?DateTime
    {
        return $this->exportedAt;
    }

    /**
     * @return string
     */
    public function getExportStatusName(): string
    {
        return self::getExportStatusNameByExportStatus($this->exportStatus);
    }

    /**
     * @param string $exportStatus
     * @return string
     */
    public static function getExportStatusNameByExportStatus(string $exportStatus): string
    {
        if ($exportStatus === self::EXPORT_SUCCESS) {
            return t('Přeneseno');
        }
        if ($exportStatus === self::EXPORT_NOT_YET) {
            return t('Zatím nepřeneseno');
        }
        if ($exportStatus === self::EXPORT_ERROR) {
            return t('Chyba při přenosu');
        }
    }

    /**
     * @param string|null $ean
     */
    public function setEan(?string $ean): void
    {
        $this->ean = $ean;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan[]
     */
    public function getUserTransferIdAndEan()
    {
        return $this->userTransferIdAndEan->toArray();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan $transferIdAndEan
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup|null $newPricingGroup
     */
    public function updateTransferEanAndPricingGroup(UserTransferIdAndEan $transferIdAndEan, ?PricingGroup $newPricingGroup)
    {
        $this->transferId = $transferIdAndEan->getTransferId();
        $this->ean = $transferIdAndEan->getEan();
        $this->pricingGroup = $newPricingGroup;
        $this->memberOfBushmanClub = true;
    }
}

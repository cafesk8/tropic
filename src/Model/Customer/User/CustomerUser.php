<?php

declare(strict_types=1);

namespace App\Model\Customer\User;

use App\Model\Customer\Exception\UnsupportedCustomerExportStatusException;
use App\Model\Customer\TransferIds\UserTransferId;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser as BaseCustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserData as BaseCustomerUserData;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;

/**
 * @ORM\Table(
 *     name="customer_users",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="email_domain", columns={"email", "domain_id"})
 *     },
 *     indexes={
 *         @ORM\Index(columns={"email"})
 *     }
 * )
 * @ORM\Entity
 * @property \App\Model\Customer\DeliveryAddress|null $defaultDeliveryAddress
 * @property \App\Model\Pricing\Group\PricingGroup $pricingGroup
 * @method \App\Model\Pricing\Group\PricingGroup getPricingGroup()
 * @method \App\Model\Customer\DeliveryAddress|null getDefaultDeliveryAddress()
 */
class CustomerUser extends BaseCustomerUser
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
     * @var \Doctrine\Common\Collections\Collection|\App\Model\Customer\TransferIds\UserTransferId[]
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Model\Customer\TransferIds\UserTransferId",
     *     mappedBy="customer",
     *     cascade={"remove"},
     *     orphanRemoval=true
     * )
     */
    private $userTransferId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $pricingGroupUpdatedAt;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true, unique=true)
     */
    private $pohodaId;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true, unique=true)
     */
    private $legacyId;

    /**
     * Did legacy user set new password in new eshop?
     *
     * @ORM\Column(type="boolean")
     */
    private bool $newPasswordSet;

    /**
     * @param \App\Model\Customer\User\CustomerUserData $customerUserData
     */
    public function __construct(BaseCustomerUserData $customerUserData)
    {
        parent::__construct($customerUserData);

        $this->transferId = $customerUserData->transferId;
        $this->exportStatus = $customerUserData->exportStatus;
        $this->pricingGroupUpdatedAt = $customerUserData->pricingGroupUpdatedAt;
        $this->userTransferId = new ArrayCollection();
        $this->pohodaId = $customerUserData->pohodaId;
        $this->legacyId = $customerUserData->legacyId;
        $this->newPasswordSet = $customerUserData->newPasswordSet;
    }

    /**
     * @param \App\Model\Customer\User\CustomerUserData $customerUserData
     */
    public function edit(BaseCustomerUserData $customerUserData)
    {
        parent::edit($customerUserData);
        $this->pricingGroupUpdatedAt = $customerUserData->pricingGroupUpdatedAt;

        $this->setExportStatus(self::EXPORT_NOT_YET);

        $this->pohodaId = $customerUserData->pohodaId;
        $this->legacyId = $customerUserData->legacyId;
        $this->newPasswordSet = $customerUserData->newPasswordSet;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->lastName . ' ' . $this->firstName;
    }

    /**
     * @return string|null
     */
    public function getTransferId(): ?string
    {
        return $this->transferId;
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
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
     * @return string
     */
    public function getExportStatus(): string
    {
        return $this->exportStatus;
    }

    /**
     * @param string $exportStatus
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

        return '';
    }

    /**
     * @return \App\Model\Customer\TransferIds\UserTransferId[]
     */
    public function getUserTransferId(): array
    {
        return $this->userTransferId->toArray();
    }

    /**
     * @param \App\Model\Customer\TransferIds\UserTransferId $transferId
     * @param \App\Model\Pricing\Group\PricingGroup|null $newPricingGroup
     */
    public function updateTransferIdAndPricingGroup(UserTransferId $transferId, ?PricingGroup $newPricingGroup)
    {
        $this->transferId = $transferId->getTransferId();
        $this->pricingGroup = $newPricingGroup;
    }

    /**
     * @return \DateTime
     */
    public function getPricingGroupUpdatedAt(): \DateTime
    {
        return $this->pricingGroupUpdatedAt;
    }

    /**
     * @return int|null
     */
    public function getPohodaId(): ?int
    {
        return $this->pohodaId;
    }

    /**
     * @return int|null
     */
    public function getLegacyId(): ?int
    {
        return $this->legacyId;
    }

    /**
     * @return bool
     */
    public function isNewPasswordSet(): bool
    {
        return $this->newPasswordSet;
    }

    /**
     * @param string $passwordHash
     */
    public function setPasswordHash(string $passwordHash): void
    {
        parent::setPasswordHash($passwordHash);
        $this->newPasswordSet = true;
    }
}

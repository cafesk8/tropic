<?php

declare(strict_types=1);

namespace App\Model\Customer\User;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddress;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser as BaseCustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserData as BaseCustomerUserData;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use App\Model\Customer\Exception\UnsupportedCustomerExportStatusException;
use App\Model\Customer\TransferIds\UserTransferId;

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
 * @property \App\Model\Customer\DeliveryAddress|null $deliveryAddress
 * @property \App\Model\Pricing\Group\PricingGroup $pricingGroup
 * @method \App\Model\Customer\DeliveryAddress|null getDeliveryAddress()
 * @method \App\Model\Pricing\Group\PricingGroup getPricingGroup()
 * @method setDeliveryAddress(\App\Model\Customer\DeliveryAddress|null $deliveryAddress)
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
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $memberOfLoyaltyProgram;

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
     * @param \App\Model\Customer\User\CustomerUserData $customerUserData
     * @param \App\Model\Customer\DeliveryAddress|null $deliveryAddress
     */
    public function __construct(
        BaseCustomerUserData $customerUserData,
        ?DeliveryAddress $deliveryAddress
    ) {
        parent::__construct($customerUserData, $deliveryAddress);

        $this->transferId = $customerUserData->transferId;
        $this->memberOfLoyaltyProgram = $customerUserData->memberOfLoyaltyProgram;
        $this->exportStatus = $customerUserData->exportStatus;
        $this->pricingGroupUpdatedAt = $customerUserData->pricingGroupUpdatedAt;
        $this->userTransferId = new ArrayCollection();
    }

    /**
     * @param \App\Model\Customer\User\CustomerUserData $customerUserData
     */
    public function edit(BaseCustomerUserData $customerUserData)
    {
        parent::edit($customerUserData);
        $this->memberOfLoyaltyProgram = $customerUserData->memberOfLoyaltyProgram;
        $this->pricingGroupUpdatedAt = $customerUserData->pricingGroupUpdatedAt;

        $this->setExportStatus(self::EXPORT_NOT_YET);
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
     * @return bool
     */
    public function isMemberOfLoyaltyProgram(): bool
    {
        return $this->memberOfLoyaltyProgram;
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
        $this->memberOfLoyaltyProgram = true;
    }

    /**
     * @return \DateTime
     */
    public function getPricingGroupUpdatedAt(): \DateTime
    {
        return $this->pricingGroupUpdatedAt;
    }
}

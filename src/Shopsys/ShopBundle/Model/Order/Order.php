<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use GoPay\Definition\Response\PaymentStatus;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\Utils\Utils;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem;
use Shopsys\FrameworkBundle\Model\Order\Order as BaseOrder;
use Shopsys\FrameworkBundle\Model\Order\OrderData as BaseOrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderEditResult;
use Shopsys\ShopBundle\Model\Order\Exception\UnsupportedOrderExportStatusException;
use Shopsys\ShopBundle\Model\Store\Store;
use Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace;
use Shopsys\ShopBundle\Model\Transport\Transport;

/**
 * @ORM\Table(name="orders")
 * @ORM\Entity
 *
 * @property \Shopsys\ShopBundle\Model\Transport\Transport $transport
 * @method \Shopsys\ShopBundle\Model\Transport\Transport getTransport()
 * @method \Shopsys\ShopBundle\Model\Payment\Payment getPayment()
 * @method \Shopsys\ShopBundle\Model\Country\Country getCountry()
 * @method \Shopsys\ShopBundle\Model\Country\Country getDeliveryCountry()
 */
class Order extends BaseOrder
{
    public const EXPORT_SUCCESS = 'export_success';
    public const EXPORT_NOT_YET = 'export_not_yet';
    public const EXPORT_ERROR = 'export_error';
    public const PROMO_CODES_SEPARATOR = ';';

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=60, nullable=true)
     */
    protected $firstName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     */
    protected $email;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $telephone;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $companyNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $companyTaxNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $street;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $city;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    protected $postcode;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=60)
     */
    protected $deliveryFirstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=30)
     */
    protected $deliveryLastName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=6)
     */
    protected $deliveryPostcode;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\Shopsys\ShopBundle\Model\GoPay\GoPayTransaction[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Shopsys\ShopBundle\Model\GoPay\GoPayTransaction",
     *     mappedBy="order",
     *     cascade={"remove"},
     * )
     * @ORM\OrderBy({"goPayId" = "ASC"})
     */
    private $goPayTransactions;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $payPalId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $payPalStatus;

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace|null
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace")
     * @ORM\JoinColumn(nullable=true, name="pickup_place_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $pickupPlace;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\Store|null
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Store\Store")
     * @ORM\JoinColumn(nullable=true, name="store_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $store;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $storeExternalNumber;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $updatedAt;

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
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $customerTransferId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $statusCheckedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $mallOrderId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $mallStatus;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $gtmCoupons;

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
    private $transportType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $promoCodesCodes;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $trackingNumber;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     * @param string $orderNumber
     * @param string $urlHash
     * @param \Shopsys\FrameworkBundle\Model\Customer\User|null $user
     */
    public function __construct(
        BaseOrderData $orderData,
        string $orderNumber,
        string $urlHash,
        ?User $user = null
    ) {
        parent::__construct($orderData, $orderNumber, $urlHash, $user);

        $this->setTransport($orderData);
        $this->payment = $orderData->payment;

        $this->setDeliveryAddressNewly($orderData);
        $this->goPayTransactions = new ArrayCollection();

        $this->note = $orderData->note;
        $this->items = new ArrayCollection();
        $this->setCompanyInfo(
            $orderData->companyName,
            $orderData->companyNumber,
            $orderData->companyTaxNumber
        );
        $this->setBillingAddress($orderData);

        $this->number = $orderNumber;
        $this->status = $orderData->status;
        $this->customer = $user;
        $this->deleted = false;
        if ($orderData->createdAt === null) {
            $this->createdAt = new DateTime();
        } else {
            $this->createdAt = $orderData->createdAt;
        }
        $this->domainId = $orderData->domainId;
        $this->urlHash = $urlHash;
        $this->currency = $orderData->currency;
        $this->createdAsAdministrator = $orderData->createdAsAdministrator;
        $this->createdAsAdministratorName = $orderData->createdAsAdministratorName;

        $this->payPalId = $orderData->payPalId;
        $this->payPalStatus = $orderData->payPalStatus;
        $this->updatedAt = $orderData->updatedAt;
        $this->exportStatus = $orderData->exportStatus;
        $this->exportedAt = $orderData->exportedAt;
        $this->mallOrderId = $orderData->mallOrderId;
        $this->mallStatus = $orderData->mallStatus;
        $this->statusCheckedAt = $orderData->statusCheckedAt;
        $this->gtmCoupons = $this->getPromoCodesString($orderData->gtmCoupons);
        $this->memberOfBushmanClub = $orderData->memberOfBushmanClub;
        $this->promoCodesCodes = $this->getPromoCodesString($orderData->promoCodesCodes);
        $this->trackingNumber = $orderData->trackingNumber;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation $orderItemPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactoryInterface $orderItemFactory
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation $orderPriceCalculation
     * @return \Shopsys\FrameworkBundle\Model\Order\OrderEditResult
     */
    public function edit(
        BaseOrderData $orderData
    ): OrderEditResult {
        $orderEditResult = parent::edit($orderData);

        $this->goPayTransactions = $orderData->goPayTransactions;
        $this->payPalId = $orderData->payPalId;
        $this->payPalStatus = $orderData->payPalStatus;
        $this->pickupPlace = $orderData->pickupPlace;
        $this->store = $orderData->store;
        $this->storeExternalNumber = $orderData->store !== null ? $orderData->store->getExternalNumber() : null;
        $this->updatedAt = $orderData->updatedAt;
        $this->mallOrderId = $orderData->mallOrderId;
        $this->mallStatus = $orderData->mallStatus;
        $this->statusCheckedAt = $orderData->statusCheckedAt;
        $this->gtmCoupons = $this->getPromoCodesString($orderData->gtmCoupons);
        $this->memberOfBushmanClub = $orderData->memberOfBushmanClub;
        $this->promoCodesCodes = $this->getPromoCodesString($orderData->promoCodesCodes);
        $this->trackingNumber = $orderData->trackingNumber;

        return $orderEditResult;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     */
    protected function setBillingAddress(BaseOrderData $orderData)
    {
        $this->deliveryAddressSameAsBillingAddress = $orderData->deliveryAddressSameAsBillingAddress;
        $this->firstName = Utils::ifNull($orderData->firstName, $orderData->deliveryFirstName);
        $this->lastName = Utils::ifNull($orderData->lastName, $orderData->deliveryLastName);
        $this->telephone = Utils::ifNull($orderData->telephone, $orderData->deliveryTelephone);
        $this->companyName = $orderData->companyName;
        $this->street = $orderData->street;
        $this->city = $orderData->city;
        $this->postcode = $orderData->postcode;
        $this->country = $orderData->country;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\GoPay\GoPayTransaction[]
     */
    public function getGoPayTransactions(): array
    {
        return $this->goPayTransactions->toArray();
    }

    /**
     * @return string[]
     */
    public function getGoPayTransactionsIndexedByGoPayId(): array
    {
        $returnArray = [];
        foreach ($this->goPayTransactions as $transaction) {
            $returnArray[$transaction->getGoPayId()] = $transaction->getGoPayStatus();
        }

        return $returnArray;
    }

    /**
     * @return string|null
     */
    public function getPayPalId(): ?string
    {
        return $this->payPalId;
    }

    /**
     * @param string|null $payPalId
     */
    public function setPayPalId(?string $payPalId): void
    {
        $this->payPalId = $payPalId;
    }

    /**
     * @param string|null $payPalStatus
     */
    public function setPayPalStatus(?string $payPalStatus): void
    {
        $this->payPalStatus = $payPalStatus;
    }

    /**
     * @return string|null
     */
    public function getPayPalStatus(): ?string
    {
        return $this->payPalStatus;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace|null
     */
    public function getPickupPlace(): ?PickupPlace
    {
        return $this->pickupPlace;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Store\Store|null
     */
    public function getStore(): ?Store
    {
        return $this->store;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
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
            throw new UnsupportedOrderExportStatusException(
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
        if ($this->exportStatus === self::EXPORT_SUCCESS) {
            return t('Přeneseno');
        }
        if ($this->exportStatus === self::EXPORT_NOT_YET) {
            return t('Zatím nepřeneseno');
        }
        if ($this->exportStatus === self::EXPORT_ERROR) {
            return t('Chyba při přenosu');
        }

        return '';
    }

    /**
     * @return string|null
     */
    public function getCustomerTransferId(): ?string
    {
        return $this->customerTransferId;
    }

    /**
     * @param string|null $customerTransferId
     */
    public function setCustomerTransferId(?string $customerTransferId): void
    {
        $this->customerTransferId = $customerTransferId;
    }

    /**
     * @return bool
     */
    public function isMemberOfBushmanClub(): bool
    {
        return $this->memberOfBushmanClub;
    }

    /**
     * @param bool $memberOfBushmanClub
     */
    public function setMemberOfBushmanClub(bool $memberOfBushmanClub): void
    {
        $this->memberOfBushmanClub = $memberOfBushmanClub;
    }

    /**
     * @return string
     */
    public function getTransportType(): string
    {
        return $this->transportType;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getOrderDiscountPrice(): Money
    {
        $discountPriceWithVat = Money::zero();

        /** @var \Shopsys\ShopBundle\Model\Order\Item\OrderItem $item */
        foreach ($this->getItems() as $item) {
            if ($item->isTypePromoCode()) {
                $discountPriceWithVat = $discountPriceWithVat->add($item->getPriceWithVat());
            }
        }

        return $discountPriceWithVat;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\OrderItem[]
     */
    public function getGiftItems()
    {
        $giftItems = [];
        foreach ($this->items as $item) {
            if ($item->isTypeGift()) {
                $giftItems[] = $item;
            }
        }

        return $giftItems;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Order\Item\OrderItem[]
     */
    public function getPromoProductItems()
    {
        $promoProductItems = [];
        foreach ($this->items as $item) {
            if ($item->isTypePromoProduct()) {
                $promoProductItems[] = $item;
            }
        }

        return $promoProductItems;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     */
    private function setDeliveryAddressNewly(OrderData $orderData): void
    {
        $this->deliveryCompanyName = $orderData->deliveryCompanyName;
        $this->deliveryFirstName = $orderData->deliveryFirstName ?? $orderData->firstName;
        $this->deliveryLastName = $orderData->deliveryLastName ?? $orderData->lastName;
        $this->email = $orderData->email;
        $this->deliveryTelephone = $orderData->deliveryTelephone ?? $orderData->telephone;
        $this->deliveryStreet = $orderData->deliveryStreet;
        $this->deliveryCity = $orderData->deliveryCity;
        $this->deliveryPostcode = $orderData->deliveryPostcode;
        $this->deliveryCountry = $orderData->deliveryCountry;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\OrderItem[]
     */
    public function getGiftCertificationItems()
    {
        $giftItems = [];
        foreach ($this->items as $item) {
            if ($item->isTypeGiftCertification()) {
                $giftItems[] = $item;
            }
        }

        return $giftItems;
    }

    /**
     * @return string|null
     */
    public function getMallOrderId(): ?string
    {
        return $this->mallOrderId;
    }

    /**
     * @return string|null
     */
    public function getMallStatus(): ?string
    {
        return $this->mallStatus;
    }

    /**
     * @return string|null
     */
    public function getStoreExternalNumber(): ?string
    {
        return $this->storeExternalNumber;
    }

    /**
     * @return \DateTime
     */
    public function getStatusCheckedAt(): \DateTime
    {
        return $this->statusCheckedAt;
    }

    /**
     * @return \DateTime
     */
    public function updateStatusCheckedAt(): void
    {
        $this->statusCheckedAt = new DateTime();
    }

    /**
     * @return string|string
     */
    public function getGtmCoupons(): ?string
    {
        return $this->gtmCoupons;
    }

    /**
     * @return string
     */
    public function getDeliveryNumberFromStreet(): string
    {
        $deliveryStreetExplodedBySpaces = explode(' ', $this->deliveryStreet);
        return array_pop($deliveryStreetExplodedBySpaces);
    }

    /**
     * @return string
     */
    public function getDeliveryStreetWihoutNumber(): string
    {
        $deliveryStreetExplodedBySpaces = explode(' ', $this->deliveryStreet);
        array_pop($deliveryStreetExplodedBySpaces);
        return implode(' ', $deliveryStreetExplodedBySpaces);
    }

    /**
     * @return bool
     */
    public function isPersonalTakeType(): bool
    {
        return in_array($this->transportType, [
            Transport::TYPE_PERSONAL_TAKE_BALIKOBOT,
            Transport::TYPE_PERSONAL_TAKE_STORE,
        ], true);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Order\Item\OrderItem
     */
    public function getPreparedProductItems(): array
    {
        return array_filter(
            $this->items->toArray(),
            function (OrderItem $orderItem) {
                /** @var \Shopsys\ShopBundle\Model\Order\Item\OrderItem $orderItem */
                return $orderItem->isTypeProduct() === true && $orderItem->getPreparedQuantity() > 0;
            }
        );
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderData $orderData
     */
    private function setTransport(BaseOrderData $orderData): void
    {
        $this->transport = $orderData->transport;

        /** @var \Shopsys\ShopBundle\Model\Transport\Transport $transport */
        $transport = $this->transport;
        if ($transport === null) {
            return;
        }

        $this->transportType = $transport->getTransportType();
        if ($transport->isPickupPlace() === true) {
            $this->pickupPlace = $orderData->pickupPlace;
        } elseif ($transport->isChooseStore() === true && $orderData->store !== null) {
            $this->store = $orderData->store;
            $this->storeExternalNumber = $this->store->getExternalNumber();
        }
    }

    /**
     * @return string|null
     */
    public function getPromoCodesCodes(): ?string
    {
        return $this->promoCodesCodes;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User $customer
     */
    public function setCustomer(User $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * @return string|null
     */
    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    /**
     * @return string|null
     */
    public function getTrackingUrl(): ?string
    {
        if ($this->trackingNumber !== null && $this->transport->getTrackingUrlPattern() !== null) {
            return sprintf($this->transport->getTrackingUrlPattern(), $this->trackingNumber);
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isGopayPaid(): bool
    {
        foreach ($this->goPayTransactions->toArray() as $item) {
            if ($item->getGoPayStatus() === PaymentStatus::PAID) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[]|null $promoCodesCodes
     * @return string|null
     */
    private function getPromoCodesString(?array $promoCodesCodes): ?string
    {
        $emptyCodes = $promoCodesCodes === null || count($promoCodesCodes) === 0;

        return !$emptyCodes ? implode(self::PROMO_CODES_SEPARATOR, $promoCodesCodes) : null;
    }
}

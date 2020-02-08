<?php

declare(strict_types=1);

namespace App\Model\Order;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use GoPay\Definition\Response\PaymentStatus;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\Utils\Utils;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem;
use Shopsys\FrameworkBundle\Model\Order\Order as BaseOrder;
use Shopsys\FrameworkBundle\Model\Order\OrderData as BaseOrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderEditResult;
use App\Model\Order\Exception\UnsupportedOrderExportStatusException;
use App\Model\Store\Store;
use App\Model\Transport\PickupPlace\PickupPlace;
use App\Model\Transport\Transport;

/**
 * @ORM\Table(name="orders")
 * @ORM\Entity
 *
 * @property \App\Model\Transport\Transport $transport
 * @method \App\Model\Transport\Transport getTransport()
 * @method \App\Model\Payment\Payment getPayment()
 * @method \App\Model\Country\Country getCountry()
 * @property \App\Model\Customer\User\CustomerUser|null $customerUser
 * @property \App\Model\Order\Item\OrderItem[]|\Doctrine\Common\Collections\Collection $items
 * @property \App\Model\Payment\Payment $payment
 * @property \App\Model\Order\Status\OrderStatus $status
 * @property \App\Model\Country\Country $country
 * @property \App\Model\Country\Country|null $deliveryCountry
 * @property \App\Model\Administrator\Administrator|null $createdAsAdministrator
 * @method editData(\App\Model\Order\OrderData $orderData)
 * @method editOrderTransport(\App\Model\Order\OrderData $orderData)
 * @method editOrderPayment(\App\Model\Order\OrderData $orderData)
 * @method setDeliveryAddress(\App\Model\Order\OrderData $orderData)
 * @method addItem(\App\Model\Order\Item\OrderItem $item)
 * @method removeItem(\App\Model\Order\Item\OrderItem $item)
 * @method setStatus(\App\Model\Order\Status\OrderStatus $status)
 * @method \App\Model\Order\Item\OrderItem getOrderPayment()
 * @method \App\Model\Order\Item\OrderItem getOrderTransport()
 * @method \App\Model\Order\Status\OrderStatus getStatus()
 * @method \App\Model\Customer\User\CustomerUser|null getCustomer()
 * @method \App\Model\Order\Item\OrderItem[] getItems()
 * @method \App\Model\Order\Item\OrderItem[] getItemsWithoutTransportAndPayment()
 * @method \App\Model\Order\Item\OrderItem[] getTransportAndPaymentItems()
 * @method \App\Model\Order\Item\OrderItem getItemById(int $orderItemId)
 * @method \App\Model\Country\Country|null getDeliveryCountry()
 * @method \App\Model\Order\Item\OrderItem[] getProductItems()
 * @method \App\Model\Administrator\Administrator|null getCreatedAsAdministrator()
 * @property \App\Model\Pricing\Currency\Currency $currency
 * @method \App\Model\Pricing\Currency\Currency getCurrency()
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
     * @var \Doctrine\Common\Collections\ArrayCollection|\App\Model\GoPay\GoPayTransaction[]|array
     *
     * @ORM\OneToMany(
     *     targetEntity="App\Model\GoPay\GoPayTransaction",
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
     * @var \App\Model\Transport\PickupPlace\PickupPlace|null
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Transport\PickupPlace\PickupPlace")
     * @ORM\JoinColumn(nullable=true, name="pickup_place_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $pickupPlace;

    /**
     * @var \App\Model\Store\Store|null
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Store\Store")
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
    private $memberOfLoyaltyProgram;

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
     * @param \App\Model\Order\OrderData $orderData
     * @param string $orderNumber
     * @param string $urlHash
     * @param \App\Model\Customer\User\CustomerUser|null $customerUser
     */
    public function __construct(
        BaseOrderData $orderData,
        string $orderNumber,
        string $urlHash,
        ?CustomerUser $customerUser = null
    ) {
        parent::__construct($orderData, $orderNumber, $urlHash, $customerUser);

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
        $this->customerUser= $customerUser;
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
        $this->memberOfLoyaltyProgram = $orderData->memberOfLoyaltyProgram;
        $this->promoCodesCodes = $this->getPromoCodesString($orderData->promoCodesCodes);
        $this->trackingNumber = $orderData->trackingNumber;
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
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
        $this->memberOfLoyaltyProgram = $orderData->memberOfLoyaltyProgram;
        $this->promoCodesCodes = $this->getPromoCodesString($orderData->promoCodesCodes);
        $this->trackingNumber = $orderData->trackingNumber;

        return $orderEditResult;
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
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
     * @return \App\Model\GoPay\GoPayTransaction[]
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
     * @return \App\Model\Transport\PickupPlace\PickupPlace|null
     */
    public function getPickupPlace(): ?PickupPlace
    {
        return $this->pickupPlace;
    }

    /**
     * @return \App\Model\Store\Store|null
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
    public function isMemberOfLoyaltyProgram(): bool
    {
        return $this->memberOfLoyaltyProgram;
    }

    /**
     * @param bool $memberOfLoyaltyProgram
     */
    public function setMemberOfLoyaltyProgram(bool $memberOfLoyaltyProgram): void
    {
        $this->memberOfLoyaltyProgram = $memberOfLoyaltyProgram;
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

        /** @var \App\Model\Order\Item\OrderItem $item */
        foreach ($this->getItems() as $item) {
            if ($item->isTypePromoCode()) {
                $discountPriceWithVat = $discountPriceWithVat->add($item->getPriceWithVat());
            }
        }

        return $discountPriceWithVat;
    }

    /**
     * @return \App\Model\Order\Item\OrderItem[]
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
     * @return \App\Model\Order\Item\OrderItem[]
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
     * @param \App\Model\Order\OrderData $orderData
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
     * @return \App\Model\Order\Item\OrderItem[]
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
     * @return \App\Model\Order\Item\OrderItem[]
     */
    public function getPreparedProductItems(): array
    {
        return array_filter(
            $this->items->toArray(),
            function (OrderItem $orderItem) {
                /** @var \App\Model\Order\Item\OrderItem $orderItem */
                return $orderItem->isTypeProduct() === true && $orderItem->getPreparedQuantity() > 0;
            }
        );
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
     */
    private function setTransport(BaseOrderData $orderData): void
    {
        $this->transport = $orderData->transport;

        /** @var \App\Model\Transport\Transport|null $transport */
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
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     */
    public function setCustomer(CustomerUser $customerUser): void
    {
        $this->customerUser = $customerUser;
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

    /**
     * @return string|null
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @return string|null
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @return string|null
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return string|null
     */
    public function getPostcode()
    {
        return $this->postcode;
    }
}

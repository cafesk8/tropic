<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation;
use Shopsys\FrameworkBundle\Model\Order\Order as BaseOrder;
use Shopsys\FrameworkBundle\Model\Order\OrderData as BaseOrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderEditResult;
use Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreview;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\FrameworkBundle\Twig\NumberFormatterExtension;
use Shopsys\ShopBundle\Model\Order\Exception\UnsupportedOrderExportStatusException;
use Shopsys\ShopBundle\Model\Order\Item\OrderItemFactory;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\ShopBundle\Model\Product\Gift\ProductGiftPriceCalculation;
use Shopsys\ShopBundle\Model\Store\Store;
use Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace;

/**
 * @ORM\Table(name="orders")
 * @ORM\Entity
 *
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

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=60)
     */
    protected $firstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=30)
     */
    protected $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=20)
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
     * @var string
     *
     * @ORM\Column(type="string", length=6)
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
     * @var string
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $goPayId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    private $goPayStatus;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=39, nullable=true)
     */
    private $goPayFik;

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
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true, length=13)
     */
    private $customerEan;

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
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $gtmCoupon;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $memberOfBushmanClub;

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

        $this->transport = $orderData->transport;
        $this->payment = $orderData->payment;

        $this->setDeliveryAddressNewly($orderData);

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

        $this->goPayId = $orderData->goPayId;
        $this->goPayStatus = $orderData->goPayStatus;
        $this->payPalId = $orderData->payPalId;
        $this->payPalStatus = $orderData->payPalStatus;
        $this->updatedAt = $orderData->updatedAt;

        /** @var \Shopsys\ShopBundle\Model\Transport\Transport $transport */
        $transport = $this->transport;

        if ($this->transport !== null && $transport->isPickupPlace()) {
            $this->pickupPlace = $orderData->pickupPlace;
        }

        if ($this->transport !== null && $transport->isChooseStore() && $orderData->store !== null) {
            $this->store = $orderData->store;
            $this->storeExternalNumber = $orderData->store->getExternalNumber();
        }

        $this->exportStatus = $orderData->exportStatus;
        $this->exportedAt = $orderData->exportedAt;
        $this->mallOrderId = $orderData->mallOrderId;
        $this->mallStatus = $orderData->mallStatus;
        $this->statusCheckedAt = $orderData->statusCheckedAt;
        $this->gtmCoupon = $orderData->gtmCoupon;
        $this->memberOfBushmanClub = $orderData->memberOfBushmanClub;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation $orderItemPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactoryInterface $orderItemFactory
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation $orderPriceCalculation
     * @return \Shopsys\FrameworkBundle\Model\Order\OrderEditResult
     */
    public function edit(
        BaseOrderData $orderData,
        OrderItemPriceCalculation $orderItemPriceCalculation,
        OrderItemFactoryInterface $orderItemFactory,
        OrderPriceCalculation $orderPriceCalculation
    ): OrderEditResult {
        $orderEditResult = parent::edit($orderData, $orderItemPriceCalculation, $orderItemFactory, $orderPriceCalculation);

        $this->goPayId = $orderData->goPayId;
        $this->goPayStatus = $orderData->goPayStatus;
        $this->payPalId = $orderData->payPalId;
        $this->payPalStatus = $orderData->payPalStatus;
        $this->pickupPlace = $orderData->pickupPlace;
        $this->store = $orderData->store;
        $this->storeExternalNumber = $orderData->store !== null ? $orderData->store->getExternalNumber() : null;
        $this->updatedAt = $orderData->updatedAt;
        $this->mallOrderId = $orderData->mallOrderId;
        $this->mallStatus = $orderData->mallStatus;
        $this->statusCheckedAt = $orderData->statusCheckedAt;
        $this->gtmCoupon = $orderData->gtmCoupon;
        $this->memberOfBushmanClub = $orderData->memberOfBushmanClub;

        return $orderEditResult;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     */
    protected function setBillingAddress(BaseOrderData $orderData)
    {
        $this->deliveryAddressSameAsBillingAddress = $orderData->deliveryAddressSameAsBillingAddress;
        if ($orderData->deliveryAddressSameAsBillingAddress) {
            $this->firstName = $orderData->deliveryFirstName;
            $this->lastName = $orderData->deliveryLastName;
            $this->companyName = $orderData->deliveryCompanyName;
            $this->telephone = $orderData->deliveryTelephone ?? '';
            $this->street = $orderData->deliveryStreet;
            $this->city = $orderData->deliveryCity;
            $this->postcode = $orderData->deliveryPostcode;
            $this->country = $orderData->deliveryCountry;
        } else {
            $this->firstName = $orderData->firstName ?? '';
            $this->lastName = $orderData->lastName ?? '';
            $this->companyName = $orderData->companyName;
            $this->telephone = $orderData->telephone ?? '';
            $this->street = $orderData->street ?? '';
            $this->city = $orderData->city ?? '';
            $this->postcode = $orderData->postcode ?? '';
            $this->country = $orderData->country;
        }

        $this->firstName = $this->firstName === '' ? $this->deliveryFirstName : $this->firstName;
        $this->lastName = $this->lastName === '' ? $this->deliveryLastName : $this->lastName;
        $this->telephone = $this->telephone === '' ? $this->deliveryTelephone : $this->telephone;
    }

    /**
     * @return string|null
     */
    public function getGoPayId(): ?string
    {
        return $this->goPayId;
    }

    /**
     * @param string|null $goPayId
     */
    public function setGoPayId(?string $goPayId): void
    {
        $this->goPayId = $goPayId;
    }

    /**
     * @return string|null
     */
    public function getGoPayStatus(): ?string
    {
        return $this->goPayStatus;
    }

    /**
     * @param string $goPayStatus
     */
    public function setGoPayStatus(string $goPayStatus): void
    {
        $this->goPayStatus = $goPayStatus;
    }

    /**
     * @return string|null
     */
    public function getGoPayFik(): ?string
    {
        return $this->goPayFik;
    }

    /**
     * @param string|null $goPayFik
     */
    public function setGoPayFik(?string $goPayFik)
    {
        $this->goPayFik = $goPayFik;
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
     * @return string|null
     */
    public function getCustomerEan(): ?string
    {
        return $this->customerEan;
    }

    /**
     * @param string|null $customerEan
     */
    public function setCustomerEan(?string $customerEan): void
    {
        $this->customerEan = $customerEan;
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
     * @param \Shopsys\ShopBundle\Twig\NumberFormatterExtension $numberFormatterExtension
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview
     * @param \Shopsys\ShopBundle\Model\Order\Item\OrderItemFactory $orderItemFactory
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $quantifiedItemDiscount
     * @param \Shopsys\ShopBundle\Model\Order\Item\OrderItem $orderItem
     * @param string $locale
     */
    protected function addOrderItemDiscount(
        NumberFormatterExtension $numberFormatterExtension,
        OrderPreview $orderPreview,
        OrderItemFactoryInterface $orderItemFactory,
        Price $quantifiedItemDiscount,
        OrderItem $orderItem,
        $locale
    ) {
        if ($orderPreview->getPromoCode()->isUseNominalDiscount()) {
            $discountValue = $numberFormatterExtension->formatNumber(-$orderPreview->getPromoCode()->getNominalDiscount()->getAmount()) . ' ' . $numberFormatterExtension->getCurrencySymbolByCurrencyIdAndLocale($orderItem->getOrder()->getDomainId(), $locale);
        } else {
            $discountValue = $numberFormatterExtension->formatPercent(-$orderPreview->getPromoCodeDiscountPercent(), $locale);
        }

        $name = sprintf(
            '%s %s - %s',
            t('Promo code', [], 'messages', $locale),
            $discountValue,
            $orderItem->getName()
        );

        $orderItemFactory->createPromoCode(
            $name,
            $quantifiedItemDiscount->inverse(),
            $orderItem
        );
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
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactoryInterface $orderItemFactory
     * @param \Shopsys\ShopBundle\Model\Product\Gift\ProductGiftPriceCalculation $productGiftPriceCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function fillOrderGifts(OrderPreview $orderPreview, OrderItemFactoryInterface $orderItemFactory, ProductGiftPriceCalculation $productGiftPriceCalculation, Domain $domain): void
    {
        /** @var \Shopsys\ShopBundle\Model\Cart\Item\CartItem $giftInCart */
        foreach ($orderPreview->getGifts() as $giftInCart) {
            $gift = $giftInCart->getProduct();

            if (!$gift instanceof Product) {
                $message = 'Object "' . get_class($gift) . '" is not valid for order creation.';
                throw new \Shopsys\FrameworkBundle\Model\Order\Item\Exception\InvalidQuantifiedProductException($message);
            }

            if (!$orderItemFactory instanceof OrderItemFactory) {
                $message = 'Object "' . get_class($orderItemFactory) . '" has to be instance of \Shopsys\ShopBundle\Model\Order\Item\OrderItemFactory.';
                throw new \Symfony\Component\Config\Definition\Exception\InvalidTypeException($message);
            }

            $giftPrice = new Price($productGiftPriceCalculation->getGiftPrice(), $productGiftPriceCalculation->getGiftPrice());
            $giftTotalPrice = new Price(
                $productGiftPriceCalculation->getGiftPrice()->multiply($giftInCart->getQuantity()),
                $productGiftPriceCalculation->getGiftPrice()->multiply($giftInCart->getQuantity())
            );

            $orderItemFactory->createGift(
                $this,
                $gift->getName($domain->getLocale()),
                $giftPrice,
                $gift->getVat()->getPercent(),
                $giftInCart->getQuantity(),
                $gift->getUnit()->getName($domain->getLocale()),
                $gift->getCatnum(),
                $gift,
                $giftTotalPrice
            );
        }
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
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     */
    private function setDeliveryAddressNewly(OrderData $orderData): void
    {
        $this->deliveryFirstName = $orderData->deliveryFirstName;
        $this->deliveryLastName = $orderData->deliveryLastName;
        $this->email = $orderData->email;
        $this->deliveryTelephone = $orderData->deliveryTelephone;
        $this->deliveryStreet = $orderData->deliveryStreet;
        $this->deliveryCity = $orderData->deliveryCity;
        $this->deliveryPostcode = $orderData->deliveryPostcode;
        $this->deliveryCountry = $orderData->deliveryCountry;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreview $orderPreview
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactoryInterface $orderItemFactory
     * @param \Shopsys\FrameworkBundle\Twig\NumberFormatterExtension $numberFormatterExtension
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     * @param string $defaultVatValue
     * @param string $locale
     */
    public function setGiftCertificate(
        OrderPreview $orderPreview,
        OrderItemFactoryInterface $orderItemFactory,
        NumberFormatterExtension $numberFormatterExtension,
        self $order,
        PromoCode $promoCode,
        string $defaultVatValue,
        string $locale
    ): void {
        $name = sprintf(
            '%s %s %s',
            t('Dárkový certifikát ', [], 'messages', $locale),
            $promoCode->getCode(),
            $numberFormatterExtension->formatNumber($promoCode->getCertificateValue()->getAmount()) . ' ' . $numberFormatterExtension->getCurrencySymbolByCurrencyIdAndLocale($order->getDomainId(), $locale)
        );

        $certificatePrice = new Price($promoCode->getCertificateValue(), $promoCode->getCertificateValue());
        if ($certificatePrice->getPriceWithVat()->isGreaterThan($orderPreview->getTotalPriceWithoutGiftCertificate()->getPriceWithVat())) {
            $certificatePrice = $orderPreview->getTotalPriceWithoutGiftCertificate();
        }

        $orderItemFactory->createGiftCertificate(
            $order,
            $name,
            $certificatePrice,
            $promoCode->getCertificateSku(),
            $defaultVatValue
        );
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
    public function getGtmCoupon(): ?string
    {
        return $this->gtmCoupon;
    }
}

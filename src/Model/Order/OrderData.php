<?php

declare(strict_types=1);

namespace App\Model\Order;

use App\Model\Transport\Transport;
use DateTime;
use Shopsys\FrameworkBundle\Model\Order\OrderData as BaseOrderData;

/**
 * @property \App\Model\Payment\Payment|null $payment
 * @property \App\Model\Order\Status\OrderStatus|null $status
 * @property \App\Model\Country\Country|null $country
 * @property \App\Model\Country\Country|null $deliveryCountry
 * @property \App\Model\Order\Item\OrderItemData[] $itemsWithoutTransportAndPayment
 * @property \App\Model\Administrator\Administrator|null $createdAsAdministrator
 * @property \App\Model\Order\Item\OrderItemData|null $orderPayment
 * @property \App\Model\Order\Item\OrderItemData|null $orderTransport
 * @method \App\Model\Order\Item\OrderItemData[] getNewItemsWithoutTransportAndPayment()
 * @property \App\Model\Pricing\Currency\Currency|null $currency
 */
class OrderData extends BaseOrderData
{
    /**
     * @var int|null
     */
    public $goPayId;

    /**
     * @var \App\Model\GoPay\GoPayTransaction[]
     */
    public $goPayTransactions;

    /**
     * @var string|null
     */
    public $payPalId;

    /**
     * @var string|null
     */
    public $payPalStatus;

    /**
     * @var \App\Model\Transport\PickupPlace\PickupPlace|null
     */
    public $pickupPlace;

    /**
     * @var \App\Model\Transport\Transport|null
     */
    public $transport;

    /**
     * @var \App\Model\Store\Store|null
     */
    public $store;

    /**
     * @var \DateTime
     */
    public $updatedAt;

    /**
     * @var \DateTime
     */
    public $statusCheckedAt;

    /**
     * @var string
     */
    public $exportStatus = Order::EXPORT_NOT_YET;

    /**
     * @var \DateTime|null
     */
    public $exportedAt;

    /**
     * @var string|null
     */
    public $mallOrderId;

    /**
     * @var string|null
     */
    public $mallStatus;

    /**
     * @var string|null
     */
    public $transportType;

    /**
     * @var string[]|null
     */
    public $gtmCoupons;

    /**
     * @var string[]|null
     */
    public $promoCodesCodes;

    /**
     * @var string|null
     */
    public $trackingNumber;

    /**
     * @var bool
     */
    public $registration;

    /**
     * @var \App\Model\Order\GiftCertificate\OrderGiftCertificate[]
     */
    public $giftCertificates;

    public ?int $pohodaId;

    public ?int $legacyId;

    public bool $disallowHeurekaVerifiedByCustomers = false;

    public function __construct()
    {
        parent::__construct();
        $this->updatedAt = new DateTime();
        $this->statusCheckedAt = new DateTime();
        $this->transportType = Transport::TYPE_NONE;
        $this->promoCodesCodes = [];
        $this->registration = false;
        $this->giftCertificates = [];
        $this->pohodaId = null;
        $this->legacyId = null;
    }
}

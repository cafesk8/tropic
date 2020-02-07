<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use DateTime;
use Shopsys\FrameworkBundle\Model\Order\OrderData as BaseOrderData;
use Shopsys\ShopBundle\Model\Transport\Transport;

/**
 * @property \Shopsys\ShopBundle\Model\Payment\Payment|null $payment
 * @property \Shopsys\ShopBundle\Model\Order\Status\OrderStatus|null $status
 * @property \Shopsys\ShopBundle\Model\Country\Country|null $country
 * @property \Shopsys\ShopBundle\Model\Country\Country|null $deliveryCountry
 * @property \Shopsys\ShopBundle\Model\Order\Item\OrderItemData[] $itemsWithoutTransportAndPayment
 * @property \Shopsys\ShopBundle\Model\Administrator\Administrator|null $createdAsAdministrator
 * @property \Shopsys\ShopBundle\Model\Order\Item\OrderItemData|null $orderPayment
 * @property \Shopsys\ShopBundle\Model\Order\Item\OrderItemData|null $orderTransport
 * @method \Shopsys\ShopBundle\Model\Order\Item\OrderItemData[] getNewItemsWithoutTransportAndPayment()
 * @property \Shopsys\ShopBundle\Model\Pricing\Currency\Currency|null $currency
 */
class OrderData extends BaseOrderData
{
    /**
     * @var int|null
     */
    public $goPayId;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\GoPayTransaction[]
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
     * @var \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace|null
     */
    public $pickupPlace;

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\Transport|null
     */
    public $transport;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\Store|null
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
     * @var bool
     */
    public $memberOfBushmanClub;

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

    public function __construct()
    {
        parent::__construct();
        $this->updatedAt = new DateTime();
        $this->statusCheckedAt = new DateTime();
        $this->memberOfBushmanClub = false;
        $this->transportType = Transport::TYPE_NONE;
        $this->promoCodesCodes = [];
    }
}

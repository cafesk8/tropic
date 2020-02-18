<?php

declare(strict_types=1);

namespace App\Model\Order;

use Shopsys\FrameworkBundle\Model\Order\FrontOrderData as BaseFrontOrderData;

/**
 * @property \App\Model\Transport\Transport|null $transport
 * @property \App\Model\Payment\Payment|null $payment
 * @property \App\Model\Order\Status\OrderStatus|null $status
 * @property \App\Model\Country\Country|null $deliveryCountry
 * @property \App\Model\Order\Item\OrderItemData[] $itemsWithoutTransportAndPayment
 * @property \App\Model\Administrator\Administrator|null $createdAsAdministrator
 * @property \App\Model\Order\Item\OrderItemData|null $orderPayment
 * @property \App\Model\Order\Item\OrderItemData|null $orderTransport
 * @method \App\Model\Order\Item\OrderItemData[] getNewItemsWithoutTransportAndPayment()
 * @property \App\Model\Pricing\Currency\Currency|null $currency
 */
class FrontOrderData extends BaseFrontOrderData
{
    /**
     * @var \App\Model\GoPay\BankSwift\GoPayBankSwift
     */
    public $goPayBankSwift;

    /**
     * @var \App\Model\Transport\PickupPlace\PickupPlace|null
     */
    public $pickupPlace;

    /**
     * @var \App\Model\Store\Store|null
     */
    public $store;

    /**
     * @var \App\Model\Country\Country|null
     */
    public $country;

    public function __construct()
    {
        parent::__construct();

        $this->newsletterSubscription = true;
    }
}

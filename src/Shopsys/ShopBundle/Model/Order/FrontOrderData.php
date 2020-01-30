<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use Shopsys\FrameworkBundle\Model\Order\FrontOrderData as BaseFrontOrderData;

/**
 * @property \Shopsys\ShopBundle\Model\Transport\Transport|null $transport
 * @property \Shopsys\ShopBundle\Model\Payment\Payment|null $payment
 * @property \Shopsys\ShopBundle\Model\Order\Status\OrderStatus|null $status
 * @property \Shopsys\ShopBundle\Model\Country\Country|null $deliveryCountry
 * @property \Shopsys\ShopBundle\Model\Order\Item\OrderItemData[] $itemsWithoutTransportAndPayment
 * @property \Shopsys\ShopBundle\Model\Administrator\Administrator|null $createdAsAdministrator
 * @property \Shopsys\ShopBundle\Model\Order\Item\OrderItemData|null $orderPayment
 * @property \Shopsys\ShopBundle\Model\Order\Item\OrderItemData|null $orderTransport
 * @method \Shopsys\ShopBundle\Model\Order\Item\OrderItemData[] getNewItemsWithoutTransportAndPayment()
 * @property \Shopsys\ShopBundle\Model\Pricing\Currency\Currency|null $currency
 */
class FrontOrderData extends BaseFrontOrderData
{
    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwift
     */
    public $goPayBankSwift;

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace|null
     */
    public $pickupPlace;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\Store|null
     */
    public $store;

    /**
     * @var \Shopsys\ShopBundle\Model\Country\Country|null
     */
    public $country;

    public function __construct()
    {
        parent::__construct();

        $this->newsletterSubscription = true;
    }
}

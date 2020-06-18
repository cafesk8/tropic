<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer;

use App\Component\Transfer\Pohoda\Customer\PohodaAddress;
use App\Component\Transfer\Pohoda\Order\PohodaCurrency;
use App\Component\Transfer\Pohoda\Order\PohodaOrder;
use App\Component\Transfer\Pohoda\Order\PohodaOrderItem;
use App\Model\Order\Item\OrderItem;
use App\Model\Order\ItemSourceStock\OrderItemSourceStockFacade;
use App\Model\Order\Order;
use App\Model\Store\Store;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;

class PohodaOrderMapper
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private PricingGroupSettingFacade $pricingGroupSettingFacade;

    /**
     * @var \App\Model\Order\ItemSourceStock\OrderItemSourceStockFacade
     */
    private OrderItemSourceStockFacade $orderItemSourceStockFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \App\Model\Order\ItemSourceStock\OrderItemSourceStockFacade $orderItemSourceStockFacade
     */
    public function __construct(PricingGroupSettingFacade $pricingGroupSettingFacade, OrderItemSourceStockFacade $orderItemSourceStockFacade)
    {
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
        $this->orderItemSourceStockFacade = $orderItemSourceStockFacade;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param array $pohodaVatNames
     * @return \App\Component\Transfer\Pohoda\Order\PohodaOrder
     */
    public function mapOrderToPohodaOrder(Order $order, array $pohodaVatNames): PohodaOrder
    {
        $pohodaOrder = new PohodaOrder();
        $pohodaOrder->dataPackItemId = $order->getNumber() . '-' . $order->getId();
        $pohodaOrder->eshopId = $order->getId();
        $pohodaOrder->number = $order->getNumber();
        $pohodaOrder->date = $order->getCreatedAt();
        $pohodaOrder->status = $order->getStatus()->getId();
        $pohodaOrder->customerEshopId = $order->getCustomerUser() === null ? null : $order->getCustomerUser()->getId();
        $pohodaOrder->totalPriceWithVat = $order->getTotalPriceWithVat();
        $pohodaOrder->pohodaTransportId = $order->getTransport()->getExternalId();
        $pohodaOrder->pohodaPaymentName = $order->getPayment()->getExternalId();

        $this->mapAddresses($order, $pohodaOrder);
        $this->mapOrderItems($order, $pohodaOrder, $pohodaVatNames);
        $this->mapCurrency($order, $pohodaOrder);
        $this->mapPricingGroup($order, $pohodaOrder);

        return $pohodaOrder;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrder $pohodaOrder
     */
    private function mapAddresses(Order $order, PohodaOrder $pohodaOrder): void
    {
        $pohodaBillingAddress = new PohodaAddress();
        $pohodaBillingAddress->company = $order->getCompanyName();
        $pohodaBillingAddress->ico = $order->getCompanyNumber();
        $pohodaBillingAddress->dic = $order->getCompanyTaxNumber();
        $pohodaBillingAddress->name = $order->getFirstName() . ' ' . $order->getLastName();
        $pohodaBillingAddress->city = $order->getCity();
        $pohodaBillingAddress->street = $order->getStreet();
        $pohodaBillingAddress->zip = $order->getPostcode();
        $pohodaBillingAddress->country = $order->getCountry()->getCode();
        $pohodaBillingAddress->email = $order->getEmail();
        $pohodaBillingAddress->phone = $order->getTelephone();
        $pohodaOrder->address = $pohodaBillingAddress;

        $pohodaDeliveryAddress = new PohodaAddress();
        if ($order->isDeliveryAddressSameAsBillingAddress()) {
            $pohodaDeliveryAddress = clone $pohodaBillingAddress;
        } else {
            $pohodaDeliveryAddress->company = $order->getDeliveryCompanyName();
            $pohodaDeliveryAddress->name = $order->getDeliveryFirstName() . ' ' . $order->getDeliveryLastName();
            $pohodaDeliveryAddress->city = $order->getDeliveryCity();
            $pohodaDeliveryAddress->street = $order->getDeliveryStreet();
            $pohodaDeliveryAddress->zip = $order->getDeliveryPostcode();
            if ($order->getDeliveryCountry() !== null) {
                $pohodaDeliveryAddress->country = $order->getDeliveryCountry()->getCode();
            }
            $pohodaDeliveryAddress->email = $order->getEmail();
            $pohodaDeliveryAddress->phone = $order->getTelephone();
        }
        $pohodaOrder->shipToAddress = $pohodaDeliveryAddress;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrder $pohodaOrder
     * @param array $pohodaVatNames
     */
    private function mapOrderItems(Order $order, PohodaOrder $pohodaOrder, array $pohodaVatNames): void
    {
        foreach ($order->getItems() as $orderItem) {
            $orderItemSourceStocks = $this->orderItemSourceStockFacade->getAllByOrderItem($orderItem);
            if (count($orderItemSourceStocks) > 0) {
                $orderItemSourceStocks = $this->orderItemSourceStockFacade->getAllByOrderItem($orderItem);
                foreach ($orderItemSourceStocks as $orderItemSourceStock) {
                    $this->mapOrderItem($order, $pohodaOrder, $pohodaVatNames, $orderItemSourceStock->getOrderItem(), $orderItemSourceStock->getQuantity(), $orderItemSourceStock->getStock());
                }
            } else {
                $this->mapOrderItem($order, $pohodaOrder, $pohodaVatNames, $orderItem, $orderItem->getQuantity());
            }
        }
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrder $pohodaOrder
     * @param array $pohodaVatNames
     * @param \App\Model\Order\Item\OrderItem $orderItem
     * @param int $quantity
     * @param \App\Model\Store\Store|null $stock
     */
    private function mapOrderItem(Order $order, PohodaOrder $pohodaOrder, array $pohodaVatNames, OrderItem $orderItem, int $quantity, ?Store $stock = null): void
    {
        $pohodaOrderItem = new PohodaOrderItem();
        $pohodaOrderItem->name = $orderItem->getName();
        $pohodaOrderItem->catnum = $orderItem->getCatnum();
        $pohodaOrderItem->quantity = $quantity;
        $pohodaOrderItem->unit = $orderItem->getUnitName();
        $pohodaOrderItem->unitPriceWithVat = $orderItem->getPriceWithVat();
        $pohodaOrderItem->vatRate = $pohodaVatNames[$order->getDomainId()][(int)$orderItem->getVatPercent()] ?? null;
        $pohodaOrderItem->vatPercent = $orderItem->getVatPercent();
        if ($stock !== null) {
            $pohodaOrderItem->pohodaStockId = $stock->getExternalNumber();
        }

        $pohodaOrder->orderItems[] = $pohodaOrderItem;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrder $pohodaOrder
     */
    private function mapCurrency(Order $order, PohodaOrder $pohodaOrder): void
    {
        $pohodaCurrency = new PohodaCurrency();
        $pohodaCurrency->code = $order->getCurrency()->getCode();
        $pohodaOrder->currency = $pohodaCurrency;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrder $pohodaOrder
     */
    private function mapPricingGroup(Order $order, PohodaOrder $pohodaOrder): void
    {
        $customerUser = $order->getCustomerUser();
        if ($customerUser === null) {
            /** @var \App\Model\Pricing\Group\PricingGroup $defaultPricingGroup */
            $defaultPricingGroup = $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($order->getDomainId());
            $pohodaOrder->pricingGroup = $defaultPricingGroup->getPohodaIdent();
        } else {
            $pohodaOrder->pricingGroup = $customerUser->getPricingGroup()->getPohodaIdent();
        }
    }
}

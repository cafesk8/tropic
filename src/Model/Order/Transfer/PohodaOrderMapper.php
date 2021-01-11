<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\Pohoda\Customer\PohodaAddress;
use App\Component\Transfer\Pohoda\Order\PohodaCurrency;
use App\Component\Transfer\Pohoda\Order\PohodaOrder;
use App\Component\Transfer\Pohoda\Order\PohodaOrderItem;
use App\Model\Order\Item\OrderItem;
use App\Model\Order\ItemSourceStock\OrderItemSourceStockFacade;
use App\Model\Order\Order;
use App\Model\Product\Unit\UnitFacade;
use App\Model\Store\Store;
use App\Model\Store\StoreFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;

class PohodaOrderMapper
{
    private PricingGroupSettingFacade $pricingGroupSettingFacade;

    private OrderItemSourceStockFacade $orderItemSourceStockFacade;

    private StoreFacade $storeFacade;

    private UnitFacade $unitFacade;

    /** @var array<\App\Model\Order\ItemSourceStock\OrderItemSourceStock[]> */
    private array $orderItemSourceStockCache = [];

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \App\Model\Order\ItemSourceStock\OrderItemSourceStockFacade $orderItemSourceStockFacade
     * @param \App\Model\Store\StoreFacade $storeFacade
     * @param \App\Model\Product\Unit\UnitFacade $unitFacade
     */
    public function __construct(
        PricingGroupSettingFacade $pricingGroupSettingFacade,
        OrderItemSourceStockFacade $orderItemSourceStockFacade,
        StoreFacade $storeFacade,
        UnitFacade $unitFacade
    ) {
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
        $this->orderItemSourceStockFacade = $orderItemSourceStockFacade;
        $this->storeFacade = $storeFacade;
        $this->unitFacade = $unitFacade;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param array $pohodaVatNames
     * @return \App\Component\Transfer\Pohoda\Order\PohodaOrder
     */
    public function mapOrderToPohodaOrder(Order $order, array $pohodaVatNames): PohodaOrder
    {
        $pohodaOrder = new PohodaOrder();

        $this->mapBasicInformation($order, $pohodaOrder);
        $this->mapInternalNote($order, $pohodaOrder);
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
    private function mapBasicInformation(Order $order, PohodaOrder $pohodaOrder): void
    {
        $pohodaOrder->dataPackItemId = $order->getNumber() . '-' . $order->getId();
        $pohodaOrder->eshopId = $order->getId();
        $pohodaOrder->number = $order->getNumber();
        $pohodaOrder->date = $order->getCreatedAt();
        $pohodaOrder->status = $order->getStatus()->getId();
        $pohodaOrder->pohodaStatusName = $order->getStatus()->getTransferStatus();
        $pohodaOrder->customerEshopId = $order->getCustomerUser() === null ? null : $order->getCustomerUser()->getId();
        $pohodaOrder->totalPriceWithVat = $order->getTotalPriceWithVat();
        $pohodaOrder->pohodaTransportId = $order->getTransport()->getExternalId();
        $pohodaOrder->pohodaPaymentName = $order->getPayment()->getExternalId();
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrder $pohodaOrder
     */
    private function mapInternalNote(Order $order, PohodaOrder $pohodaOrder): void
    {
        $internalNoteParts = [];

        if ($order->getDomainId() === DomainHelper::SLOVAK_DOMAIN) {
            $internalNoteParts[] = 'Slovensko';
        }

        if ($order->getPayment()->waitsForPayment()) {
            $internalNoteParts[] = 'Čekat na platbu';
        }

        if ($this->isImmediatelyAvailable($order)) {
            $internalNoteParts[] = 'Ihned k odeslání';
        }

        $pohodaOrder->internalNote = implode(' + ', $internalNoteParts);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrder $pohodaOrder
     */
    private function mapAddresses(Order $order, PohodaOrder $pohodaOrder): void
    {
        $pohodaBillingAddress = new PohodaAddress();
        $pohodaDeliveryAddress = new PohodaAddress();

        if ($order->getTransport()->isPickupPlaceType()) {
            $this->mapBasicDeliveryAddress($order, $pohodaDeliveryAddress);

            if ($order->isDeliveryAddressSameAsBillingAddress()) {
                $pohodaBillingAddress->name = $order->getFirstName() . ' ' . $order->getLastName();
                $pohodaBillingAddress->phone = $order->getTelephone();
                $pohodaBillingAddress->email = $order->getEmail();
                $pohodaBillingAddress->country = $order->getCountry()->getCode();
            } else {
                $this->mapFullBillingAddress($order, $pohodaBillingAddress);
            }
        } else {
            $this->mapFullBillingAddress($order, $pohodaBillingAddress);

            if ($order->isDeliveryAddressSameAsBillingAddress()) {
                $pohodaDeliveryAddress = clone $pohodaBillingAddress;
            } else {
                $this->mapBasicDeliveryAddress($order, $pohodaDeliveryAddress);
                if ($order->getDeliveryCountry() !== null) {
                    $pohodaDeliveryAddress->country = $order->getDeliveryCountry()->getCode();
                }
                $pohodaDeliveryAddress->email = $order->getEmail();
                $pohodaDeliveryAddress->phone = $order->getTelephone();
            }
        }

        $pohodaOrder->address = $pohodaBillingAddress;
        $pohodaOrder->shipToAddress = $pohodaDeliveryAddress;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Component\Transfer\Pohoda\Customer\PohodaAddress $pohodaDeliveryAddress
     */
    private function mapBasicDeliveryAddress(Order $order, PohodaAddress $pohodaDeliveryAddress): void
    {
        $pohodaDeliveryAddress->company = $order->getDeliveryCompanyName();
        $pohodaDeliveryAddress->name = $order->getDeliveryFirstName() . ' ' . $order->getDeliveryLastName();
        $pohodaDeliveryAddress->city = $order->getDeliveryCity();
        $pohodaDeliveryAddress->street = $order->getDeliveryStreet();
        $pohodaDeliveryAddress->zip = $order->getDeliveryPostcode();
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Component\Transfer\Pohoda\Customer\PohodaAddress $pohodaBillingAddress
     */
    private function mapFullBillingAddress(Order $order, PohodaAddress $pohodaBillingAddress): void
    {
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
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrder $pohodaOrder
     * @param array $pohodaVatNames
     */
    private function mapOrderItems(Order $order, PohodaOrder $pohodaOrder, array $pohodaVatNames): void
    {
        foreach ($order->getItems() as $orderItem) {
            $orderItemSourceStocks = $this->getOrderItemSourceStocks($orderItem);
            if (count($orderItemSourceStocks) > 0) {
                foreach ($orderItemSourceStocks as $orderItemSourceStock) {
                    $stock = $orderItemSourceStock->getStock();
                    /*
                     * If is order item from external stock, we need force internal stock - because external stock is only virtual in Pohoda
                     * External stock is not stock in Pohoda but only basic text input
                     */
                    $isFromExternalStock = false;
                    if ($stock->isExternalStock()) {
                        $stock = $this->storeFacade->findInternalStock();
                        $isFromExternalStock = true;
                    }
                    $this->mapOrderItem($order, $pohodaOrder, $pohodaVatNames, $orderItemSourceStock->getOrderItem(), $orderItemSourceStock->getQuantity(), $stock, $isFromExternalStock);
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
     * @param bool $isFromExternalStock
     */
    private function mapOrderItem(Order $order, PohodaOrder $pohodaOrder, array $pohodaVatNames, OrderItem $orderItem, int $quantity, ?Store $stock = null, bool $isFromExternalStock = false): void
    {
        $pohodaOrderItem = new PohodaOrderItem();
        $pohodaOrderItem->name = mb_substr($orderItem->getName(), 0, PohodaOrderItem::POHODA_NAME_MAX_LENGTH);
        $pohodaOrderItem->catnum = $orderItem->getCatnum();
        $pohodaOrderItem->quantity = $quantity;

        if ($orderItem->getUnitName() !== null) {
            $pohodaOrderItem->unit = $this->unitFacade->getByNameAndLocale($orderItem->getUnitName(), DomainHelper::DOMAIN_ID_TO_LOCALE[$order->getDomainId()])->getPohodaName();
        }

        $pohodaOrderItem->unitPriceWithVat = $orderItem->getPriceWithVat();
        $pohodaOrderItem->vatRate = $pohodaVatNames[$order->getDomainId()][(int)$orderItem->getVatPercent()] ?? null;
        $pohodaOrderItem->vatPercent = $orderItem->getVatPercent();
        $pohodaOrderItem->pohodaStockId = $stock === null ? null : $stock->getExternalNumber();
        $pohodaOrderItem->pohodaStockName = $stock === null ? null : $stock->getPohodaName();
        $pohodaOrderItem->isFromExternalStock = $isFromExternalStock;

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

    /**
     * @param \App\Model\Order\Item\OrderItem $orderItem
     * @return \App\Model\Order\ItemSourceStock\OrderItemSourceStock[]
     */
    private function getOrderItemSourceStocks(OrderItem $orderItem): array
    {
        if (!isset($this->orderItemSourceStockCache[$orderItem->getId()])) {
            $this->orderItemSourceStockCache[$orderItem->getId()] = $this->orderItemSourceStockFacade->getAllByOrderItem($orderItem);
        }

        return $this->orderItemSourceStockCache[$orderItem->getId()];
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return bool
     */
    private function isImmediatelyAvailable(Order $order): bool
    {
        foreach ($order->getItems() as $orderItem) {
            foreach ($this->getOrderItemSourceStocks($orderItem) as $orderItemSourceStock) {
                $stock = $orderItemSourceStock->getStock();

                if (!$stock->isSaleStock() && !$stock->isInternalStock()) {
                    return false;
                }
            }
        }

        return true;
    }
}

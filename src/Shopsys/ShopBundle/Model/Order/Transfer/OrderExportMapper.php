<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Transfer;

use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\FrameworkBundle\Model\Pricing\InputPriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Rounding;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Transfer\TransferConfig;
use Shopsys\ShopBundle\Model\Order\Item\OrderItem;
use Shopsys\ShopBundle\Model\Order\Order;

class OrderExportMapper
{
    private const MALL_SOURCE = 'MALL';

    private const EMPTY_VALUE = 'empty';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Rounding
     */
    private $rounding;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Rounding $rounding
     */
    public function __construct(Rounding $rounding)
    {
        $this->rounding = $rounding;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return array
     */
    public function mapToArray(Order $order): array
    {
        $orderArray = [];
        $orderArray['Header'] = $this->prepareHeader($order);
        $orderArray['Items'] = $this->prepareItems($order);

        return $orderArray;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return array
     */
    private function prepareHeader(Order $order): array
    {
        $headerArray = [
            'Source' => $this->getSource($order),
            'Number' => $order->getNumber(),
            'CreatingDateTime' => $order->getCreatedAt()->format(TransferConfig::DATETIME_FORMAT),
            'Customer' => [
                'ID' => $order->getCustomerTransferId(),
                'Adress' => [
                    'SureName' => $order->getLastName(),
                    'ForeName' => $order->getFirstName(),
                    'Company' => $order->getCompanyName(),
                    'Street' => TransformString::emptyToNull($order->getStreet()) ?? self::EMPTY_VALUE,
                    'City' => TransformString::emptyToNull($order->getCity()) ?? self::EMPTY_VALUE,
                    'ZIP' => TransformString::emptyToNull($order->getPostcode()) ?? self::EMPTY_VALUE,
                    'Country' => $this->getCountryPropertyContent($order),
                ],
                'ICO' => $order->getCompanyNumber(),
                'DIC' => $order->getCompanyTaxNumber(),
                'Phone' => $order->getTelephone(),
                'Email' => $order->getEmail(),
            ],
            'NumberOfItems' => $order->getProductItemsCount(),
            'Total' => $order->getTotalPriceWithVat()->getAmount(),
            'OrderDiscount' => 0.0,
            'ShippingPrice' => $order->getTransportAndPaymentPrice()->getPriceWithVat()->getAmount(),
            'PaymentMetod' => $order->getPayment()->getExternalId() ?? $order->getPaymentName(),
            'ShippingMetod' => $this->getShippingMethodPropertyContent($order),
            'CustomerNote' => $this->getCustomerNote($order),
        ];

        if ($order->isDeliveryAddressSameAsBillingAddress() === false && $order->getStoreExternalNumber() === null) {
            $headerArray['DeliveryAdress'] = [
                'SureName' => $order->getDeliveryFirstName(),
                'ForeName' => $order->getDeliveryLastName(),
                'Company' => $order->getDeliveryCompanyName(),
                'Street' => $order->getDeliveryStreet(),
                'City' => $order->getDeliveryCity(),
                'ZIP' => $order->getDeliveryPostcode(),
                'Country' => $this->getDeliveryCountryPropertyContent($order),
                'BranchNumber' => $order->isMemberOfBushmanClub() ? '1' : '0',
            ];
        } else {
            $headerArray['DeliveryAdress'] = [
                'SureName' => self::EMPTY_VALUE,
                'ForeName' => self::EMPTY_VALUE,
                'Company' => self::EMPTY_VALUE,
                'Street' => self::EMPTY_VALUE,
                'City' => self::EMPTY_VALUE,
                'ZIP' => self::EMPTY_VALUE,
                'Country' => self::EMPTY_VALUE,
                'BranchNumber' => $order->isMemberOfBushmanClub() ? '1' : '0',
            ];
        }

        if ($order->getCustomerEan() !== null) {
            $headerArray['Customer']['IdCards'][] = $order->getCustomerEan();
        }

        return $headerArray;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return string|null
     */
    private function getCustomerNote(Order $order): ?string
    {
        $customerNote = $order->getNote();
        if ($order->getPromoCodeCode() !== null) {
            $customerNote = 'Kód slevového kupónu: ' . $order->getPromoCodeCode() . "\n\n" . $customerNote;
        }

        return $customerNote;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return string
     */
    private function getCountryPropertyContent(Order $order): string
    {
        if ($order->getCountry()->getExternalId() !== null) {
            $countryPropertyContent = $order->getCountry()->getExternalId();
        } elseif ($order->getCountry()->getCode() !== null) {
            $countryPropertyContent = $order->getCountry()->getCode();
        } else {
            $countryPropertyContent = '';
        }

        return $countryPropertyContent;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return string
     */
    private function getShippingMethodPropertyContent(Order $order): string
    {
        if ($order->getStoreExternalNumber() !== null) {
            $shippingMethodPropertyContent = $order->getStoreExternalNumber();
        } elseif ($order->getTransport()->getExternalId() !== null) {
            $shippingMethodPropertyContent = $order->getTransport()->getExternalId();
        } else {
            $shippingMethodPropertyContent = $order->getTransportName();
        }

        return $shippingMethodPropertyContent;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return string
     */
    private function getDeliveryCountryPropertyContent(Order $order): string
    {
        $deliveryCountryPropertyContent = '';
        if ($order->getDeliveryCountry() !== null) {
            if ($order->getDeliveryCountry()->getExternalId() !== null) {
                $deliveryCountryPropertyContent = $order->getDeliveryCountry()->getExternalId();
            } elseif ($order->getDeliveryCountry()->getCode() !== null) {
                $deliveryCountryPropertyContent = $order->getDeliveryCountry()->getCode();
            }
        }

        return $deliveryCountryPropertyContent;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return string
     */
    private function getSource(Order $order): string
    {
        if ($order->getMallOrderId() !== null) {
            return self::MALL_SOURCE;
        }

        return DomainHelper::DOMAIN_ID_TO_TRANSFER_SOURCE[$order->getDomainId()];
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return array
     */
    private function prepareItems(Order $order): array
    {
        $orderItems = [];
        $items = array_merge($order->getProductItems(), $order->getGiftItems(), $order->getGiftCertificationItems());

        /** @var \Shopsys\ShopBundle\Model\Order\Item\OrderItem $item */
        foreach ($items as $item) {
            $orderItems[] = [
                'BarCode' => $item->getEan(),
                'Name' => $item->getName(),
                'Quantity' => $item->getQuantity(),
                'FullPrice' => $item->getPriceWithVat()->getAmount(),
                'Discount' => $this->getOrderItemDiscount($item),
            ];
        }

        return $orderItems;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Item\OrderItem $item
     * @return string
     */
    private function getOrderItemDiscount(OrderItem $item): string
    {
        $orderItemDiscount = $item->getPromoCodeForOrderItem();

        if ($orderItemDiscount !== null) {
            $discountPerUnit = $orderItemDiscount->getPriceWithVat()->divide($item->getQuantity(), InputPriceCalculation::INPUT_PRICE_SCALE);
            $discountPerUnit = $this->rounding->roundPriceWithoutVat($discountPerUnit);
            return $discountPerUnit->multiply(-1)->getAmount();
        }

        return '0.0';
    }
}

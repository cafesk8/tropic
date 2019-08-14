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
                    'Street' => TransformString::emptyToNull($order->getStreet()) ?? 'empty',
                    'City' => TransformString::emptyToNull($order->getCity()) ?? 'empty',
                    'ZIP' => TransformString::emptyToNull($order->getPostcode()) ?? 'empty',
                    'Country' => $order->getCountry() !== null ? $order->getCountry()->getCode() : '',
                    'BranchNumber' => '', // it is allowed only for delivery address (see IS documentation)
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
            'PaymentMetod' => $order->getPaymentName(),
            'ShippingMetod' => $order->getStoreExternalNumber() ?? $order->getTransportName(),
            'CustomerNote' => $order->getNote(),
        ];

        if ($order->isDeliveryAddressSameAsBillingAddress() === false) {
            $headerArray['DeliveryAdress'] = [
                'SureName' => $order->getDeliveryFirstName(),
                'ForeName' => $order->getDeliveryLastName(),
                'Company' => $order->getDeliveryCompanyName(),
                'Street' => $order->getDeliveryStreet(),
                'City' => $order->getDeliveryCity(),
                'ZIP' => $order->getDeliveryPostcode(),
                'Country' => $order->getDeliveryCountry() !== null ? $order->getCountry()->getCode() : '',
                'BranchNumber' => '', //IS was not able to tell us, what they use it for
            ];
        }

        return $headerArray;
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
        $items = array_merge($order->getProductItems(), $order->getGiftCertificationItems());

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

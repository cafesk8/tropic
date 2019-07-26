<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Transfer;

use Shopsys\ShopBundle\Component\Transfer\TransferConfig;
use Shopsys\ShopBundle\Model\Order\Order;

class OrderExportMapper
{
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
            'Source' => 'LPKCZ',
            'Number' => $order->getNumber(),
            'CreatingDateTime' => $order->getCreatedAt()->format(TransferConfig::DATETIME_FORMAT),
            'Customer' => [
                'ID' => $order->getCustomerTransferId(),
                'Adress' => [
                    'SureName' => $order->getFirstName(),
                    'ForeName' => $order->getLastName(),
                    'Company' => $order->getCompanyName(),
                    'Street' => $order->getStreet(),
                    'City' => $order->getCity(),
                    'ZIP' => $order->getPostcode(),
                    'Country' => $order->getCountry() !== null ? $order->getCountry()->getName('cs') : '',
                    'BranchNumber' => '',
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
            'ShippingMetod' => $order->getTransportName(),
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
                'Country' => $order->getDeliveryCountry() !== null ? $order->getDeliveryCountry()->getName('cs') : '',
                'BranchNumber' => '',
            ];
        }

        return $headerArray;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return array
     */
    private function prepareItems(Order $order): array
    {
        $orderItems = [];

        foreach ($order->getProductItems() as $item) {
            /** @var \Shopsys\ShopBundle\Model\Order\Item\OrderItem $item */
            $orderItems[] = [
                'BarCode' => $item->getEan(),
                'Name' => $item->getName(),
                'Quantity' => $item->getQuantity(),
                'FullPrice' => $item->getTotalPriceWithVat()->getAmount(),
                'Discount' => 0.0,
            ];
        }

        return $orderItems;
    }
}

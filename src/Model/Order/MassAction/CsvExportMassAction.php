<?php

declare(strict_types=1);

namespace App\Model\Order\MassAction;

use App\Component\String\StringHelper;
use App\Model\Order\Order;
use App\Model\Order\OrderFacade;

class CsvExportMassAction implements OrderMassAction
{
    private const WEIGHT = '2';
    private const ZERO_PRICE_WITH_VAT = '0';

    /**
     * @var string
     */
    private const CSV_FILE_DELIMITER = ';';

    /**
     * @var \App\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @param \App\Model\Order\OrderFacade $orderFacade
     */
    public function __construct(OrderFacade $orderFacade)
    {
        $this->orderFacade = $orderFacade;
    }

    /**
     * @param int[] $selectedOrdersIds
     * @return string
     */
    public function process(array $selectedOrdersIds): string
    {
        $ordersDataForCsvExport = [];
        foreach ($selectedOrdersIds as $selectedOrderId) {
            $ordersDataForCsvExport[] = implode(self::CSV_FILE_DELIMITER, $this->getExportOrderRow($this->orderFacade->getById($selectedOrderId)));
        }

        return implode(PHP_EOL, $ordersDataForCsvExport);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return array
     */
    private function getExportOrderRow(Order $order): array
    {
        $phoneNumber = $order->getDeliveryTelephone() ?? $order->getTelephone();

        $totalPriceWithVat = StringHelper::replaceDotByComma($order->getTotalPriceWithVat()->getAmount());
        $isCashOnDeliveryPayment = $order->getPayment()->isCashOnDelivery();

        return [
            $this->encodeCsv($order->getDeliveryLastName()),
            $this->encodeCsv($order->getDeliveryFirstName()),
            $this->encodeCsv($order->getDeliveryCompanyName()),
            $this->encodeCsv($order->getDeliveryCity()),
            $this->encodeCsv($order->getDeliveryStreetWihoutNumber()),
            $this->encodeCsv($order->getDeliveryPostcode()),
            $this->encodeCsv($order->getDeliveryNumberFromStreet()),
            $this->encodeCsv($phoneNumber),
            $this->encodeCsv($totalPriceWithVat),
            $this->encodeCsv($isCashOnDeliveryPayment ? $totalPriceWithVat : self::ZERO_PRICE_WITH_VAT),
            $this->encodeCsv(self::WEIGHT),
            $this->encodeCsv($order->getNumber()),
            $this->encodeCsv($order->getEmail()),
        ];
    }

    /**
     * @param string|null $string
     * @return string
     */
    private function encodeCsv(?string $string): string
    {
        if ($string === null) {
            return '';
        }

        if (strpos($string, ',') !== false || strpos($string, '"') !== false || strpos($string, "\n") !== false) {
            $string = '"' . str_replace('"', '""', $string) . '"';
        }

        return $string;
    }
}

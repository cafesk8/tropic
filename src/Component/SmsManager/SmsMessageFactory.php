<?php

declare(strict_types=1);

namespace App\Component\SmsManager;

use App\Model\Order\Order;
use App\Model\Order\Status\OrderStatus;
use SimPod\SmsManager\RequestType;
use SimPod\SmsManager\SmsMessage;

class SmsMessageFactory
{
    /**
     * @param \App\Model\Order\Order $order
     * @return \SimPod\SmsManager\SmsMessage|null
     */
    public function getSmsMessageForOrder(Order $order): ?SmsMessage
    {
        /** @var \App\Model\Order\Status\OrderStatus $orderStatus */
        $orderStatus = $order->getStatus();

        if ($orderStatus->getSmsAlertType() === null || $order->getStore() === null) {
            return null;
        }

        $smsAlert = $this->getSmsAlert(
            $orderStatus->getSmsAlertType(),
            $order->getNumber(),
            $order->getStore()->getName()
        );

        if ($smsAlert === null) {
            return null;
        }

        return new SmsMessage(
            $smsAlert,
            [
                $order->getTelephone(),
            ],
            RequestType::getRequestTypeEconomy(),
            'B2C ready-made'
        );
    }

    /**
     * @param string $smsAlertType
     * @param string $orderNumber
     * @param string $storeName
     * @return string|null
     */
    private function getSmsAlert(string $smsAlertType, string $orderNumber, string $storeName): ?string
    {
        if ($smsAlertType === OrderStatus::SMS_ALERT_5_DAY_BEFORE) {
            return t('Je čas vyrazit: vaše objednávka č. %%orderNo%% je připravena k vyzvednutí na prodejně %%storeName%%, zboží vám rezervujeme po dobu 5 dnů.', [
                '%%orderNo%%' => $orderNumber,
                '%%storeName%%' => $storeName,
            ]);
        } elseif ($smsAlertType === OrderStatus::SMS_ALERT_2_DAY_BEFORE) {
            return t('Nezapomeňte: vaše objednávka č. %%orderNo%% bude ješte 2 dny připravena na prodejně %%storeName%%. Je čas vyrazit!', [
                '%%orderNo%%' => $orderNumber,
                '%%storeName%%' => $storeName,
            ]);
        }

        return null;
    }
}

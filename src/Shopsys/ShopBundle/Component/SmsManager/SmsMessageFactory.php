<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\SmsManager;

use Shopsys\ShopBundle\Model\Order\Order;
use Shopsys\ShopBundle\Model\Order\Status\OrderStatus;
use SimPod\SmsManager\RequestType;
use SimPod\SmsManager\SmsMessage;

class SmsMessageFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return \SimPod\SmsManager\SmsMessage|null
     */
    public function getSmsMessageForOrder(Order $order): ?SmsMessage
    {
        /** @var \Shopsys\ShopBundle\Model\Order\Status\OrderStatus $orderStatus */
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
            'Shopsys'
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
            return t('Je čas vyrazit: Vaše objednávka č. %%orderNo%% je připravena k vyzvednutí na prodejně %%storeName%%, zboží vám rezervujeme po dobu 5 dnů.', [
                '%%orderNo%%' => $orderNumber,
                '%%storeName%%' => $storeName,
            ]);
        } elseif ($smsAlertType === OrderStatus::SMS_ALERT_2_DAY_BEFORE) {
            return t('Nezapomeňte: Vaše objednávka č. %%orderNo%% bude ješte 2 dny připravena na prodejně %%storeName%%.', [
                '%%orderNo%%' => $orderNumber,
                '%%storeName%%' => $storeName,
            ]);
        }

        return null;
    }
}

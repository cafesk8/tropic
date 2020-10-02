<?php

declare(strict_types=1);

namespace App\Component\Cofidis;

use App\Model\Order\Order;

class CofidisFacade
{
    private CofidisClientFactory $cofidisClientFactory;

    private CofidisOrderMapper $cofidisOrderMapper;

    private array $paymentLinks;

    /**
     * @param \App\Component\Cofidis\CofidisClientFactory $cofidisClientFactory
     * @param \App\Component\Cofidis\CofidisOrderMapper $cofidisOrderMapper
     */
    public function __construct(
        CofidisClientFactory $cofidisClientFactory,
        CofidisOrderMapper $cofidisOrderMapper
    ) {
        $this->cofidisClientFactory = $cofidisClientFactory;
        $this->cofidisOrderMapper = $cofidisOrderMapper;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return string|null
     */
    public function sendPaymentToCofidis(Order $order): ?string
    {
        $cofidisClient = $this->cofidisClientFactory->create();
        $cofidisPaymentData = $this->cofidisOrderMapper->createCofidisPaymentData($order, $cofidisClient->getConfig());

        return $cofidisClient->sendPaymentToCofidis($cofidisPaymentData);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return string|null
     */
    public function getCofidisPaymentLink(Order $order): ?string
    {
        if (!$order->getPayment()->isCofidis()) {
            return null;
        }

        if (isset($this->paymentLinks[$order->getId()]) && !empty($this->paymentLinks[$order->getId()])) {
            return $this->paymentLinks[$order->getId()];
        }

        $paymentLink = $this->sendPaymentToCofidis($order);
        $this->paymentLinks[$order->getId()] = $paymentLink;

        return $paymentLink;
    }
}

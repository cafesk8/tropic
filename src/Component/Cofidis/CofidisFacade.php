<?php

declare(strict_types=1);

namespace App\Component\Cofidis;

use App\Model\Order\Order;

class CofidisFacade
{
    /**
     * @var \App\Component\Cofidis\CofidisClientFactory
     */
    private $cofidisClientFactory;

    /**
     * @var \App\Component\Cofidis\CofidisOrderMapper
     */
    private $cofidisOrderMapper;

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
}

<?php

declare(strict_types=1);

namespace App\Component\Cofidis;

use App\Model\Order\Order;
use Shopsys\FrameworkBundle\Component\Domain\Domain;

class CofidisFacade
{
    /**
     * @var \App\Component\Cofidis\CofidisClientFactory
     */
    private $cofidisClientFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Component\Cofidis\CofidisOrderMapper
     */
    private $cofidisOrderMapper;

    /**
     * @param \App\Component\Cofidis\CofidisClientFactory $cofidisClientFactory
     * @param \App\Component\Cofidis\CofidisOrderMapper $cofidisOrderMapper
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        CofidisClientFactory $cofidisClientFactory,
        CofidisOrderMapper $cofidisOrderMapper,
        Domain $domain
    ) {
        $this->cofidisClientFactory = $cofidisClientFactory;
        $this->domain = $domain;
        $this->cofidisOrderMapper = $cofidisOrderMapper;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return string|null
     */
    public function sendPaymentToCofidis(Order $order): ?string
    {
        $cofidisClient = $this->cofidisClientFactory->createByLocale($this->domain->getLocale());
        $cofidisPaymentData = $this->cofidisOrderMapper->createCofidisPaymentData($order, $cofidisClient->getConfig());

        return $cofidisClient->sendPaymentToCofidis($cofidisPaymentData);
    }
}

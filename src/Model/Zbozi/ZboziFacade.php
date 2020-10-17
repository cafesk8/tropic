<?php

declare(strict_types=1);

namespace App\Model\Zbozi;

use App\Component\Domain\DomainHelper;
use App\Model\Order\Order;
use Soukicz\Zbozicz\Client;

class ZboziFacade
{
    private Client $client;

    private ZboziOrderFactory $zboziOrderFactory;

    /**
     * @param \Soukicz\Zbozicz\Client $client
     * @param \App\Model\Zbozi\ZboziOrderFactory $zboziOrderFactory
     */
    public function __construct(Client $client, ZboziOrderFactory $zboziOrderFactory)
    {
        $this->client = $client;
        $this->zboziOrderFactory = $zboziOrderFactory;
    }

    /**
     * @param \App\Model\Order\Order $order
     */
    public function sendOrder(Order $order): void
    {
        $locale = DomainHelper::DOMAIN_ID_TO_LOCALE[$order->getDomainId()];
        $zboziOrder = $this->zboziOrderFactory->createFromOrder($order, $locale);
        $this->client->sendOrder($zboziOrder);
    }
}

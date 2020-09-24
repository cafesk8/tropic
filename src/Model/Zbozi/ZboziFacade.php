<?php

declare(strict_types=1);

namespace App\Model\Zbozi;

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
        $zboziOrder = $this->zboziOrderFactory->createFromOrder($order);
        $this->client->sendOrder($zboziOrder);
    }
}
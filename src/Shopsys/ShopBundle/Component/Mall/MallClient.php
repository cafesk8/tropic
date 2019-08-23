<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Mall;

use MPAPI\Services\Client;

class MallClient
{
    /**
     * @var \MPAPI\Services\Client
     */
    private $client;

    /**
     * @param string $apiKey
     */
    public function __construct(
        string $apiKey
    ) {
        $this->client = new Client($apiKey, false);
    }

    /**
     * @return \MPAPI\Services\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}

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
     * @var string
     */
    private $apiKey;

    /**
     * @param string $apiKey
     */
    public function __construct(
        string $apiKey
    ) {
        $this->apiKey = $apiKey;
    }

    /**
     * @return \MPAPI\Services\Client
     */
    public function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client($this->apiKey, false);
        }
        return $this->client;
    }
}

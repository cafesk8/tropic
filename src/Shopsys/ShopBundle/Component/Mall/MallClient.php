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
     * @var bool
     */
    private $isProductionMode;

    /**
     * @param string $apiKey
     * @param bool $isProductionMode
     */
    public function __construct(
        string $apiKey,
        bool $isProductionMode
    ) {
        $this->apiKey = $apiKey;
        $this->isProductionMode = $isProductionMode;
    }

    /**
     * @return \MPAPI\Services\Client
     */
    public function getClient(): Client
    {
        if ($this->client === null) {
            if ($this->isProductionMode === true) {
                $this->client = new Client($this->apiKey, false);
            } else {
                $this->client = new Client($this->apiKey, false, 'https://test-mpapi.mallgroup.com/v1/');
            }
        }
        return $this->client;
    }
}

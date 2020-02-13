<?php

declare(strict_types=1);

namespace App\Component\Rest;

class MultidomainRestClientFactory
{
    /**
     * @var \App\Component\Rest\RestClientFactory
     */
    private $restClientFactory;

    /**
     * @param \App\Component\Rest\RestClientFactory $restClientFactory
     */
    public function __construct(RestClientFactory $restClientFactory)
    {
        $this->restClientFactory = $restClientFactory;
    }

    /**
     * @param string $host
     * @param string $czechUsername
     * @param string $czechPassword
     * @param string $slovakUsername
     * @param string $slovakPassword
     * @param string $englishUsername
     * @param string $englishPassword
     * @param int $timeout
     * @param int $connectionTimeout
     * @return \App\Component\Rest\MultidomainRestClient
     */
    public function create(
        string $host,
        string $czechUsername,
        string $czechPassword,
        string $slovakUsername,
        string $slovakPassword,
        string $englishUsername,
        string $englishPassword,
        int $timeout = 600,
        int $connectionTimeout = 120
    ): MultidomainRestClient {
        return new MultidomainRestClient(
            $this->restClientFactory->create($host, $czechUsername, $czechPassword, $timeout, $connectionTimeout),
            $this->restClientFactory->create($host, $slovakUsername, $slovakPassword, $timeout, $connectionTimeout),
            $this->restClientFactory->create($host, $englishUsername, $englishPassword, $timeout, $connectionTimeout)
        );
    }
}

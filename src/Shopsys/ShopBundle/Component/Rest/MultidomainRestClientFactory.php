<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Rest;

class MultidomainRestClientFactory
{
    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClientFactory
     */
    private $restClientFactory;

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
     * @param string $germanUsername
     * @param string $germanPassword
     * @param int $timeout
     * @param int $connectionTimeout
     * @return \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    public function create(
        string $host,
        string $czechUsername,
        string $czechPassword,
        string $slovakUsername,
        string $slovakPassword,
        string $germanUsername,
        string $germanPassword,
        int $timeout = 600,
        int $connectionTimeout = 120
    ): RestClient {
        return new MultidomainRestClient(
            $this->restClientFactory->create($host, $czechUsername, $czechPassword, $timeout, $connectionTimeout),
            $this->restClientFactory->create($host, $slovakUsername, $slovakPassword, $timeout, $connectionTimeout),
            $this->restClientFactory->create($host, $germanUsername, $germanPassword, $timeout, $connectionTimeout)
        );
    }
}

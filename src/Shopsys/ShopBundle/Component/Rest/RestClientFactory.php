<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Rest;

class RestClientFactory
{
    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param int $timeout
     * @param int $connectionTimeout
     * @return \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    public function create(
        string $host,
        string $username,
        string $password,
        int $timeout = 600,
        int $connectionTimeout = 120
    ): RestClient {
        return new RestClient($host, $username, $password, $timeout, $connectionTimeout);
    }
}

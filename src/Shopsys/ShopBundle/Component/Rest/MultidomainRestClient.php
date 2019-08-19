<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Rest;

class MultidomainRestClient
{
    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    private $czechRestClient;

    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    private $slovakRestClient;

    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    private $germanRestClient;

    /**
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $czechRestClient
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $slovakRestClient
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $germanRestClient
     */
    public function __construct(
        RestClient $czechRestClient,
        RestClient $slovakRestClient,
        RestClient $germanRestClient
    ) {
        $this->czechRestClient = $czechRestClient;
        $this->slovakRestClient = $slovakRestClient;
        $this->germanRestClient = $germanRestClient;
    }

    /**
     * @return \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    public function getCzechRestClient(): RestClient
    {
        return $this->czechRestClient;
    }

    /**
     * @return \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    public function getSlovakRestClient(): RestClient
    {
        return $this->slovakRestClient;
    }

    /**
     * @return \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    public function getGermanRestClient(): RestClient
    {
        return $this->germanRestClient;
    }
}

<?php

declare(strict_types=1);

namespace App\Component\Rest;

use App\Component\Domain\DomainHelper;

class MultidomainRestClient
{
    /**
     * @var \App\Component\Rest\RestClient
     */
    private $czechRestClient;

    /**
     * @var \App\Component\Rest\RestClient
     */
    private $slovakRestClient;

    /**
     * @var \App\Component\Rest\RestClient
     */
    private $germanRestClient;

    /**
     * @param \App\Component\Rest\RestClient $czechRestClient
     * @param \App\Component\Rest\RestClient $slovakRestClient
     * @param \App\Component\Rest\RestClient $germanRestClient
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
     * @return \App\Component\Rest\RestClient
     */
    public function getCzechRestClient(): RestClient
    {
        return $this->czechRestClient;
    }

    /**
     * @return \App\Component\Rest\RestClient
     */
    public function getSlovakRestClient(): RestClient
    {
        return $this->slovakRestClient;
    }

    /**
     * @return \App\Component\Rest\RestClient
     */
    public function getGermanRestClient(): RestClient
    {
        return $this->germanRestClient;
    }

    /**
     * @param int $domainId
     * @return \App\Component\Rest\RestClient
     */
    public function getByDomainId(int $domainId): RestClient
    {
        $restClient = $this->czechRestClient;
        if ($domainId === DomainHelper::SLOVAK_DOMAIN) {
            $restClient = $this->slovakRestClient;
        }
        if ($domainId === DomainHelper::GERMAN_DOMAIN) {
            $restClient = $this->germanRestClient;
        }

        return $restClient;
    }
}

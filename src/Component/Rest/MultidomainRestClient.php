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
    private $englishRestClient;

    /**
     * @param \App\Component\Rest\RestClient $czechRestClient
     * @param \App\Component\Rest\RestClient $slovakRestClient
     * @param \App\Component\Rest\RestClient $englishRestClient
     */
    public function __construct(
        RestClient $czechRestClient,
        RestClient $slovakRestClient,
        RestClient $englishRestClient
    ) {
        $this->czechRestClient = $czechRestClient;
        $this->slovakRestClient = $slovakRestClient;
        $this->englishRestClient = $englishRestClient;
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
    public function getEnglishRestClient(): RestClient
    {
        return $this->englishRestClient;
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
        if ($domainId === DomainHelper::ENGLISH_DOMAIN) {
            $restClient = $this->englishRestClient;
        }

        return $restClient;
    }
}

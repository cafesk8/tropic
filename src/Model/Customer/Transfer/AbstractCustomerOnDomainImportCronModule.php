<?php

declare(strict_types=1);

namespace App\Model\Customer\Transfer;

use App\Component\Rest\RestClient;
use App\Component\Transfer\Response\TransferResponse;

abstract class AbstractCustomerOnDomainImportCronModule extends AbstractCustomerImportCronModule
{
    /**
     * @return string
     */
    protected function getApiUrl(): string
    {
        return '/api/Eshop/Customers';
    }

    /**
     * @param \App\Component\Rest\RestClient $restClient
     * @return \App\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponseByRestClient(RestClient $restClient): TransferResponse
    {
        $transferDataItems = [];
        $restResponse = $restClient->get($this->getApiUrl());
        foreach ($restResponse->getData() as $restData) {
            $transferDataItems[] = new CustomerTransferResponseItemData($restData);
        }

        return new TransferResponse($restResponse->getCode(), $transferDataItems);
    }
}

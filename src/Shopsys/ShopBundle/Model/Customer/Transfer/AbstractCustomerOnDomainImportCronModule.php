<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Transfer;

use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;

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
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
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

<?php

declare(strict_types=1);

namespace App\Model\Customer\Transfer;

use App\Component\Rest\MultidomainRestClient;
use App\Model\Customer\TransferIds\CustomerInfoResponseItemData;
use App\Model\Customer\TransferIds\UserTransferId;

class CustomerTransferService
{
    /**
     * @var \App\Component\Rest\MultidomainRestClient
     */
    private $multidomainRestClient;

    /**
     * @param \App\Component\Rest\MultidomainRestClient $multidomainRestClient
     */
    public function __construct(MultidomainRestClient $multidomainRestClient)
    {
        $this->multidomainRestClient = $multidomainRestClient;
    }

    /**
     * @param \App\Model\Customer\TransferIds\UserTransferId $userTransferId
     * @param int $domainId
     * @return \App\Model\Customer\TransferIds\CustomerInfoResponseItemData|null
     */
    public function getTransferItemsFromResponse(UserTransferId $userTransferId, int $domainId): ?\App\Model\Customer\TransferIds\CustomerInfoResponseItemData
    {
        $restResponse = $this->multidomainRestClient->getByDomainId($domainId)->get('/api/Eshop/CustomerInfo', [
            'Email' => $userTransferId->getCustomer()->getEmail(),
        ]);

        return new CustomerInfoResponseItemData($restResponse->getData(), $userTransferId);
    }
}

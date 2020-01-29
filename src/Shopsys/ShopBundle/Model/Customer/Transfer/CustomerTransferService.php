<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Transfer;

use Shopsys\ShopBundle\Component\Rest\MultidomainRestClient;
use Shopsys\ShopBundle\Model\Customer\TransferIds\CustomerInfoResponseItemData;
use Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferId;

class CustomerTransferService
{
    /**
     * @var \Shopsys\ShopBundle\Component\Rest\MultidomainRestClient
     */
    private $multidomainRestClient;

    /**
     * @param \Shopsys\ShopBundle\Component\Rest\MultidomainRestClient $multidomainRestClient
     */
    public function __construct(MultidomainRestClient $multidomainRestClient)
    {
        $this->multidomainRestClient = $multidomainRestClient;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferId $userTransferId
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Customer\TransferIds\CustomerInfoResponseItemData|null
     */
    public function getTransferItemsFromResponse(UserTransferId $userTransferId, int $domainId): ?CustomerInfoResponseItemData
    {
        $restResponse = $this->multidomainRestClient->getByDomainId($domainId)->get('/api/Eshop/CustomerInfo', [
            'Email' => $userTransferId->getCustomer()->getEmail(),
        ]);

        return new CustomerInfoResponseItemData($restResponse->getData(), $userTransferId);
    }
}

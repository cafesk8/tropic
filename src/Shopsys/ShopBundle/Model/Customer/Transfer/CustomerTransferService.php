<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Transfer;

use Shopsys\ShopBundle\Component\Rest\MultidomainRestClient;
use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\CustomerInfoResponseItemData;
use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan;

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
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan $userTransferIdAndEan
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\CustomerInfoResponseItemData|null
     */
    public function getTransferItemsFromResponse(UserTransferIdAndEan $userTransferIdAndEan, int $domainId): ?CustomerInfoResponseItemData
    {
        $restResponse = $this->multidomainRestClient->getByDomainId($domainId)->get('/api/Eshop/CustomerInfo', [
            'Number' => $userTransferIdAndEan->getEan(),
            'Email' => $userTransferIdAndEan->getCustomer()->getEmail(),
        ]);

        return new CustomerInfoResponseItemData($restResponse->getData(), $userTransferIdAndEan);
    }
}

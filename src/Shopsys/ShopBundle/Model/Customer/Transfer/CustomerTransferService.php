<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Transfer;

use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\CustomerInfoResponseItemData;
use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan;

class CustomerTransferService
{
    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    private $restClient;

    /**
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     */
    public function __construct(RestClient $restClient)
    {
        $this->restClient = $restClient;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan $userTransferIdAndEan
     * @return \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\CustomerInfoResponseItemData|null
     */
    public function getTransferItemsFromResponse(UserTransferIdAndEan $userTransferIdAndEan): ?CustomerInfoResponseItemData
    {
        $apiMethodUrl = sprintf('/api/Eshop/CustomerInfo?Number=%s&Email=%s', $userTransferIdAndEan->getEan(), $userTransferIdAndEan->getCustomer()->getEmail());
        $restResponse = $this->restClient->get($apiMethodUrl);

        return new CustomerInfoResponseItemData($restResponse->getData(), $userTransferIdAndEan);
    }
}

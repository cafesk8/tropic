<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans;

use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferResponseItemData;
use Shopsys\ShopBundle\Model\Customer\User;

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
     * @return \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferResponseItemData[]
     */
    public function getCustomersResponse(): array
    {
        $restResponse = $this->restClient->get('/api/Eshop/Customers');

        $restResponseData = $restResponse->getData();
        $transferDataItems = [];
        foreach ($restResponseData as $restData) {
            $transferDataItems[] = new CustomerTransferResponseItemData($restData);
        }

        return $transferDataItems;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $customer
     * @param string $ean
     * @return mixed[]
     */
    public function getCustomersInfoResponse(User $customer, string $ean): array
    {
        $restResponse = $this->restClient->get(sprintf('/api/Eshop/CustomerInfo?Number=%s&Email=%s', $ean, $customer->getEmail()));

        return $restResponse->getData();
    }

    /**
     * @param mixed[] $responseData
     * @return bool
     */
    public function isCoeffListAttributeCorrect(array $responseData): bool
    {
        return array_key_exists('CoeffList', $responseData) === false || $responseData['CoeffList'] === null ||
            is_array($responseData['CoeffList']) === false || count($responseData['CoeffList']) === 0;
    }

    /**
     * @param mixed[] $coefListAttribute
     * @return bool
     */
    public function isCoefficientAttributeCorrect(array $coefListAttribute): bool
    {
        return array_key_exists('Coefficient', $coefListAttribute) === false || $coefListAttribute['Coefficient'] === null;
    }
}

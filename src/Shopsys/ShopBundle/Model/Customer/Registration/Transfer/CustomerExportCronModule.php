<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Registration\Transfer;

use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\String\StringHelper;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferExportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Customer\CustomerFacade;

class CustomerExportCronModule extends AbstractTransferExportCronModule
{
    private const TRANSFER_IDENTIFIER = 'export_customers';
    private const CUSTOMER_EXPORT_BATCH_SIZE = 100;

    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    private $restClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\Registration\Transfer\CustomerExportMapper
     */
    private $customerExportMapper;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\ShopBundle\Model\Customer\Registration\Transfer\CustomerExportMapper $customerExportMapper
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        RestClient $restClient,
        CustomerFacade $customerFacade,
        CustomerExportMapper $customerExportMapper
    ) {
        parent::__construct($transferCronModuleDependency);

        $this->restClient = $restClient;
        $this->customerFacade = $customerFacade;
        $this->customerExportMapper = $customerExportMapper;
    }

    /**
     * @return string
     */
    protected function getTransferIdentifier(): string
    {
        return self::TRANSFER_IDENTIFIER;
    }

    /**
     * @return array
     */
    protected function getDataForExport(): array
    {
        $notExportedCustomers = $this->customerFacade->getNotExportedCustomersBatch(self::CUSTOMER_EXPORT_BATCH_SIZE);
        $customersToExport = [];

        foreach ($notExportedCustomers as $user) {
            $customersToExport[$user->getId()] = $this->customerExportMapper->mapToArray($user);
        }

        return $customersToExport;
    }

    /**
     * @param array $orderCustomerData
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(array $orderCustomerData): TransferResponse
    {
        $restResponse = $this->restClient->post('api/Eshop/NewOrder', $orderCustomerData);

        sleep(1);

        return new TransferResponse($restResponse->getCode(), $restResponse->getData());
    }

    /**
     * @inheritDoc
     */
    protected function processExportResponse($itemIdentifier, TransferResponse $transferResponse): void
    {
        if ($transferResponse->getStatusCode() !== 200) {
            $this->customerFacade->markCustomerAsFailedExported($itemIdentifier);
            $this->logger->addError(sprintf(
                'User with id `%s` was not exported, because of bad response code `%s`',
                $itemIdentifier,
                $transferResponse->getStatusCode()
            ));
        }

        $responseData = $transferResponse->getResponseData();
        if (array_key_exists('Error', $responseData) && $responseData['Error'] === true) {
            $this->customerFacade->markCustomerAsFailedExported($itemIdentifier);
            $this->logger->addWarning(sprintf(
                'User with id `%s` was not exported, because of error `%s`',
                $itemIdentifier,
                StringHelper::removeNewline((string)$responseData['ErrorMessage'])
            ));
        } else {
            $this->customerFacade->markCustomerAsExported($itemIdentifier);
            $this->logger->addInfo(sprintf('User with id `%s` was exported successfully', $itemIdentifier));
        }
    }
}

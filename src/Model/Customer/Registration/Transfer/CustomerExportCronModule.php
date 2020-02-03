<?php

declare(strict_types=1);

namespace App\Model\Customer\Registration\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Rest\MultidomainRestClient;
use App\Component\String\StringHelper;
use App\Component\Transfer\AbstractTransferExportCronModule;
use App\Component\Transfer\Response\TransferResponse;
use App\Component\Transfer\TransferCronModuleDependency;
use App\Model\Customer\CustomerFacade;

class CustomerExportCronModule extends AbstractTransferExportCronModule
{
    private const TRANSFER_IDENTIFIER = 'export_customers';
    private const CUSTOMER_EXPORT_BATCH_SIZE = 100;

    /**
     * @var \App\Component\Rest\MultidomainRestClient
     */
    private $multidomainRestClient;

    /**
     * @var \App\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \App\Model\Customer\Registration\Transfer\CustomerExportMapper
     */
    private $customerExportMapper;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Component\Rest\MultidomainRestClient $multidomainRestClient
     * @param \App\Model\Customer\CustomerFacade $customerFacade
     * @param \App\Model\Customer\Registration\Transfer\CustomerExportMapper $customerExportMapper
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        MultidomainRestClient $multidomainRestClient,
        CustomerFacade $customerFacade,
        CustomerExportMapper $customerExportMapper
    ) {
        parent::__construct($transferCronModuleDependency);

        $this->multidomainRestClient = $multidomainRestClient;
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
     * @return \App\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(array $orderCustomerData): TransferResponse
    {
        $source = $orderCustomerData['Header']['Source'];
        $domainId = DomainHelper::TRANSFER_SOURCE_TO_DOMAIN_ID[$source];
        $restClient = $this->multidomainRestClient->getByDomainId($domainId);

        $restResponse = $restClient->post('api/Eshop/NewOrder', $orderCustomerData);

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

<?php

declare(strict_types=1);

namespace App\Model\Customer\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Rest\MultidomainRestClient;
use App\Component\Rest\RestClient;
use App\Component\Transfer\AbstractTransferImportCronModule;
use App\Component\Transfer\Response\TransferResponse;
use App\Component\Transfer\Response\TransferResponseItemDataInterface;
use App\Component\Transfer\TransferCronModuleDependency;
use App\Model\Customer\Transfer\Exception\InvalidCustomerTransferResponseItemDataException;
use App\Model\Customer\User\CustomerUserFacade;

abstract class AbstractCustomerImportCronModule extends AbstractTransferImportCronModule
{
    public const TRANSFER_IDENTIFIER = '';

    /**
     * @var \App\Component\Rest\MultidomainRestClient
     */
    protected $multidomainRestClient;

    /**
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    protected $customerUserFacade;

    /**
     * @var \App\Model\Customer\Transfer\CustomerTransferValidator
     */
    protected $customerTransferValidator;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Component\Rest\MultidomainRestClient $multidomainRestClient
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \App\Model\Customer\Transfer\CustomerTransferValidator $customerTransferValidator
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        MultidomainRestClient $multidomainRestClient,
        CustomerUserFacade $customerUserFacade,
        CustomerTransferValidator $customerTransferValidator
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->multidomainRestClient = $multidomainRestClient;
        $this->customerUserFacade = $customerUserFacade;
        $this->customerTransferValidator = $customerTransferValidator;
    }

    /**
     * @return string
     */
    protected function getTransferIdentifier(): string
    {
        return static::TRANSFER_IDENTIFIER;
    }

    /**
     * @return \App\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $this->logger->addInfo('Downloading customers for domain with ID `' . DomainHelper::CZECH_DOMAIN . '`');
        $czechTransferDataItems = $this->getTransferItemsFromResponse($this->multidomainRestClient->getCzechRestClient());
        $transferDataItems = $czechTransferDataItems;

        $this->logger->addInfo('Downloading customers for domain with ID `' . DomainHelper::SLOVAK_DOMAIN . '`');
        $slovakTransferDataItems = $this->getTransferItemsFromResponse($this->multidomainRestClient->getSlovakRestClient());
        $transferDataItems = array_merge($transferDataItems, $slovakTransferDataItems);

        $this->logger->addInfo('Downloading customers for domain with ID `' . DomainHelper::ENGLISH_DOMAIN . '`');
        $germanTransferDataItems = $this->getTransferItemsFromResponse($this->multidomainRestClient->getEnglishRestClient());
        $transferDataItems = array_merge($transferDataItems, $germanTransferDataItems);

        return new TransferResponse(200, $transferDataItems);
    }

    /**
     * @param \App\Component\Transfer\Response\TransferResponseItemDataInterface $itemData
     */
    protected function processTransferItemData(TransferResponseItemDataInterface $itemData): void
    {
        if (!($itemData instanceof CustomerTransferResponseItemData)) {
            throw new InvalidCustomerTransferResponseItemDataException(
                'Invalid argument passed into method. Instance of %s was expected',
                CustomerTransferResponseItemData::class
            );
        }

        $this->customerTransferValidator->validate($itemData);

        $customer = $this->customerUserFacade->findCustomerUserByEmailAndDomain(
            $itemData->getEmail(),
            $itemData->getDomainId()
        );

        if ($customer === null) {
            $this->logger->addInfo(sprintf('Customer with transfer ID `%s` not found, will be skipped', $itemData->getDataIdentifier()));
            return;
        }

        if ($customer->getTransferId() === null) {
            $this->customerUserFacade->editCustomerTransferId($customer, $itemData->getDataIdentifier());
            $this->logger->addInfo(sprintf(
                'Customer with transfer ID `%s`, transfer ID has been edited',
                $itemData->getDataIdentifier()
            ));
        }
    }

    /**
     * @return bool
     */
    protected function isNextIterationNeeded(): bool
    {
        return false;
    }

    /**
     * @param \App\Component\Rest\RestClient $restClient
     * @return \App\Model\Customer\Transfer\CustomerTransferResponseItemData[]
     */
    protected function getTransferItemsFromResponse(RestClient $restClient)
    {
        $restResponse = $restClient->get($this->getApiUrl());

        $restResponseData = $restResponse->getData();
        $transferDataItems = [];
        foreach ($restResponseData as $restData) {
            $transferDataItems[] = new CustomerTransferResponseItemData($restData);
        }

        return $transferDataItems;
    }

    /**
     * @return string
     */
    abstract protected function getApiUrl(): string;
}

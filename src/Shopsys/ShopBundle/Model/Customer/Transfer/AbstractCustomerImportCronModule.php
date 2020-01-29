<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Transfer;

use Shopsys\ShopBundle\Command\Migrations\Transfer\CustomerWithPricingGroupsTransferMapper;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Rest\MultidomainRestClient;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferImportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Customer\CustomerFacade;
use Shopsys\ShopBundle\Model\Customer\Transfer\Exception\InvalidCustomerTransferResponseItemDataException;
use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanFacade;
use Shopsys\ShopBundle\Model\Customer\User;

abstract class AbstractCustomerImportCronModule extends AbstractTransferImportCronModule
{
    public const TRANSFER_IDENTIFIER = '';

    /**
     * @var \Shopsys\ShopBundle\Component\Rest\MultidomainRestClient
     */
    protected $multidomainRestClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
     */
    protected $customerFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferValidator
     */
    protected $customerTransferValidator;

    /**
     * @var \Shopsys\ShopBundle\Command\Migrations\Transfer\CustomerWithPricingGroupsTransferMapper
     */
    private $customerWithPricingGroupsTransferMapper;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanFacade
     */
    private $userTransferIdAndEanFacade;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Component\Rest\MultidomainRestClient $multidomainRestClient
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferValidator $customerTransferValidator
     * @param \Shopsys\ShopBundle\Command\Migrations\Transfer\CustomerWithPricingGroupsTransferMapper $customerWithPricingGroupsTransferMapper
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanFacade $userTransferIdAndEanFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        MultidomainRestClient $multidomainRestClient,
        CustomerFacade $customerFacade,
        CustomerTransferValidator $customerTransferValidator,
        CustomerWithPricingGroupsTransferMapper $customerWithPricingGroupsTransferMapper,
        UserTransferIdAndEanFacade $userTransferIdAndEanFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->multidomainRestClient = $multidomainRestClient;
        $this->customerFacade = $customerFacade;
        $this->customerTransferValidator = $customerTransferValidator;
        $this->customerWithPricingGroupsTransferMapper = $customerWithPricingGroupsTransferMapper;
        $this->userTransferIdAndEanFacade = $userTransferIdAndEanFacade;
    }

    /**
     * @return string
     */
    protected function getTransferIdentifier(): string
    {
        return static::TRANSFER_IDENTIFIER;
    }

    /**
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $this->logger->addInfo('Downloading customers for domain with ID `' . DomainHelper::CZECH_DOMAIN . '`');
        $czechTransferDataItems = $this->getTransferItemsFromResponse($this->multidomainRestClient->getCzechRestClient());
        $transferDataItems = $czechTransferDataItems;

        $this->logger->addInfo('Downloading customers for domain with ID `' . DomainHelper::SLOVAK_DOMAIN . '`');
        $slovakTransferDataItems = $this->getTransferItemsFromResponse($this->multidomainRestClient->getSlovakRestClient());
        $transferDataItems = array_merge($transferDataItems, $slovakTransferDataItems);

        $this->logger->addInfo('Downloading customers for domain with ID `' . DomainHelper::GERMAN_DOMAIN . '`');
        $germanTransferDataItems = $this->getTransferItemsFromResponse($this->multidomainRestClient->getGermanRestClient());
        $transferDataItems = array_merge($transferDataItems, $germanTransferDataItems);

        return new TransferResponse(200, $transferDataItems);
    }

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface $itemData
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

        /** @var \Shopsys\ShopBundle\Model\Customer\User $customer */
        $customer = $this->customerFacade->findUserByEmailAndDomain(
            $itemData->getEmail(),
            $itemData->getDomainId()
        );

        if ($customer === null) {
            // EDIT: BUSHMAN wants to edit e-mail addresses for some "lost users" who probably hasn't never visited e-shop
            // CREATE: BUSHMAN continuously wants to add new customers to IS and send to eshop
            // Try to find customer by transferId instead of email!
            $customer = $this->customerFacade->findUserByTransferIdAndDomain(
                $itemData->getDataIdentifier(),
                $itemData->getDomainId()
            );
            if ($customer === null) {
                $this->createCustomer($itemData);
            } else {
                $this->editCustomer($itemData, $customer);
            }

            return;
        }

        if ($customer->getTransferId() === null) {
            $this->customerFacade->editCustomerTransferId($customer, $itemData->getDataIdentifier());
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
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @return \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferResponseItemData[]
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

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferResponseItemData $itemData
     * @param \Shopsys\ShopBundle\Model\Customer\User $customer
     */
    private function editCustomer(CustomerTransferResponseItemData $itemData, User $customer): void
    {
        $customerData = $this->customerWithPricingGroupsTransferMapper->mapTransferDataToCustomerData($itemData, $customer);

        $this->customerFacade->editByAdmin($customer->getId(), $customerData);

        $this->em->flush($customer);

        $this->logger->addInfo(
            sprintf(
                'Customer with transfer ID `%s` has been edited',
                $itemData->getDataIdentifier()
            )
        );
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferResponseItemData $customerTransferResponseItemData
     */
    private function createCustomer(CustomerTransferResponseItemData $customerTransferResponseItemData): void
    {
        $customerData = $this->customerWithPricingGroupsTransferMapper->mapTransferDataToCustomerData($customerTransferResponseItemData, null);

        $customer = $this->customerFacade->create($customerData);
        $customer->markAsExported();

        $this->em->flush($customer);

        $this->userTransferIdAndEanFacade->saveTransferIdsAndEans($customer, $customerTransferResponseItemData->getEans(), $customerTransferResponseItemData->getDataIdentifier());

        $this->logger->addInfo(
            sprintf(
                'Customer with transfer ID `%s` has been created',
                $customerTransferResponseItemData->getDataIdentifier()
            )
        );
    }
}

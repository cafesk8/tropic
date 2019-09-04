<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Transfer;

use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferImportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Customer\CustomerFacade;
use Shopsys\ShopBundle\Model\Customer\Transfer\Exception\InvalidCustomerTransferResponseItemDataException;

class CustomerImportCronModule extends AbstractTransferImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_customers';

    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    private $restClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferValidator
     */
    private $customerTransferValidator;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferValidator $customerTransferValidator
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        RestClient $restClient,
        CustomerFacade $customerFacade,
        CustomerTransferValidator $customerTransferValidator
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->restClient = $restClient;
        $this->customerFacade = $customerFacade;
        $this->customerTransferValidator = $customerTransferValidator;
    }

    /**
     * @return string
     */
    protected function getTransferIdentifier(): string
    {
        return self::TRANSFER_IDENTIFIER;
    }

    /**
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $transfer = $this->transferFacade->getByIdentifier(self::TRANSFER_IDENTIFIER);

        if ($transfer->getLastStartAt() === false) {
            $restResponse = $this->restClient->get('/api/Eshop/Customers');
        } else {
            $restResponse = $this->restClient->get('/api/Eshop/ChangedCustomers');
        }

        $restResponseData = $restResponse->getData();
        $transferDataItems = [];
        foreach ($restResponseData as $restData) {
            $transferDataItems[] = new CustomerTransferResponseItemData($restData);
        }
        return new TransferResponse($restResponse->getCode(), $transferDataItems);
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
            DomainHelper::DOMAIN_ID_BY_COUNTRY_CODE[$itemData->getCountryCode()]
        );

        if ($customer === null) {
            $this->logger->addInfo(sprintf('Customer with transfer ID `%s` now found, will be skipped', $itemData->getDataIdentifier()));
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
}

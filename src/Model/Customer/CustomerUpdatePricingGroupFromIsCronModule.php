<?php

declare(strict_types=1);

namespace App\Model\Customer;

use App\Component\Rest\Exception\UnexpectedResponseCodeException;
use App\Component\Transfer\AbstractTransferImportCronModule;
use App\Component\Transfer\Response\TransferResponse;
use App\Component\Transfer\Response\TransferResponseItemDataInterface;
use App\Component\Transfer\TransferCronModuleDependency;
use App\Model\Customer\Transfer\CustomerTransferService;
use App\Model\Customer\TransferIds\CustomerInfoResponseItemData;
use App\Model\Customer\User\CustomerUserFacade;
use App\Model\Order\Status\Transfer\Exception\InvalidOrderStatusTransferResponseItemDataException;

class CustomerUpdatePricingGroupFromIsCronModule extends AbstractTransferImportCronModule
{
    private const TRANSFER_IDENTIFIER = 'import_customers_pricing_groups';

    /**
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    private $customerUserFacade;

    /**
     * @var \App\Model\Customer\Transfer\CustomerTransferService
     */
    private $customerTransferService;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \App\Model\Customer\Transfer\CustomerTransferService $customerTransferService
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        CustomerUserFacade $customerUserFacade,
        CustomerTransferService $customerTransferService
    ) {
        parent::__construct($transferCronModuleDependency);

        $this->customerUserFacade = $customerUserFacade;
        $this->customerTransferService = $customerTransferService;
    }

    /**
     * @return string
     */
    protected function getTransferIdentifier(): string
    {
        return self::TRANSFER_IDENTIFIER;
    }

    /**
     * @return \App\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $customers = $this->customerUserFacade->getForPricingGroupUpdate();
        $allTransferDataItems = [];
        foreach ($customers as $customer) {
            foreach ($customer->getUserTransferId() as $transferId) {
                try {
                    $allTransferDataItems[] = $this->customerTransferService->getTransferItemsFromResponse($transferId, $customer->getDomainId());
                } catch (UnexpectedResponseCodeException $unexpectedResponseCodeException) {
                    $this->customerUserFacade->changeCustomerPricingGroupUpdatedAt($transferId->getCustomer());
                    $this->logger->addWarning(sprintf('Customer info for User with email %s not found', $transferId->getCustomer()->getEmail()));
                }
            }
        }

        return new TransferResponse(200, $allTransferDataItems);
    }

    /**
     * @param \App\Component\Transfer\Response\TransferResponseItemDataInterface $customerInfoResponseItemData
     */
    protected function processTransferItemData(TransferResponseItemDataInterface $customerInfoResponseItemData): void
    {
        if (!($customerInfoResponseItemData instanceof CustomerInfoResponseItemData)) {
            throw new InvalidOrderStatusTransferResponseItemDataException(
                sprintf('Invalid argument passed into method. Instance of `%s` was expected', CustomerInfoResponseItemData::class)
            );
        }

        $this->customerUserFacade->updatePricingGroupByIsResponse(
            $customerInfoResponseItemData->getTransferId()->getCustomer()->getPricingGroup(),
            $customerInfoResponseItemData->getCoefficient(),
            $customerInfoResponseItemData->getTransferId()
        );
    }

    /**
     * @return bool
     */
    protected function isNextIterationNeeded(): bool
    {
        return false;
    }
}

<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use DateTime;
use Shopsys\ShopBundle\Component\Rest\Exception\UnexpectedResponseCodeException;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferImportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\CustomerInfoResponseItemData;
use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan;
use Shopsys\ShopBundle\Model\Order\Status\Transfer\Exception\InvalidOrderStatusTransferResponseItemDataException;

class CustomerUpdatePricingGroupFromIsCronModule extends AbstractTransferImportCronModule
{
    private const TRANSFER_IDENTIFIER = 'import_customers_pricing_groups';
    private const CUSTOMER_BATCH_SIZE = 50;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    private $restClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerDataFactory
     */
    private $customerDataFactory;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerDataFactory $customerDataFactory
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        CustomerFacade $customerFacade,
        RestClient $restClient,
        CustomerDataFactory $customerDataFactory
    ) {
        parent::__construct($transferCronModuleDependency);

        $this->customerFacade = $customerFacade;
        $this->restClient = $restClient;
        $this->customerDataFactory = $customerDataFactory;
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
        $customers = $this->customerFacade->getBatchToPricingGroupUpdate(self::CUSTOMER_BATCH_SIZE);
        $allTransferDataItems = [];
        foreach ($customers as $customer) {
            foreach ($customer->getUserTransferIdAndEan() as $transferIdAndEan) {
                $allTransferDataItems[] = $this->getTransferItemsFromResponse($transferIdAndEan);
            }
        }

        return new TransferResponse(200, $allTransferDataItems);
    }

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface $customerInfoResponseItemData
     */
    protected function processTransferItemData(TransferResponseItemDataInterface $customerInfoResponseItemData): void
    {
        if (!($customerInfoResponseItemData instanceof CustomerInfoResponseItemData)) {
            throw new InvalidOrderStatusTransferResponseItemDataException(
                sprintf('Invalid argument passed into method. Instance of `%s` was expected', CustomerInfoResponseItemData::class)
            );
        }

        $this->customerFacade->updatePricingGroupByIsResponse($customerInfoResponseItemData);
        $this->changeCustomerPricingGroupUpdatedAt($customerInfoResponseItemData->getTransferIdAndEan()->getCustomer());
    }

    /**
     * @return bool
     */
    protected function isNextIterationNeeded(): bool
    {
        return true;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan $userTransferIdAndEan
     * @return \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\CustomerInfoResponseItemData|null
     */
    private function getTransferItemsFromResponse(UserTransferIdAndEan $userTransferIdAndEan): ?CustomerInfoResponseItemData
    {
        $apiMethodUrl = sprintf('/api/Eshop/CustomerInfo?Number=%s&Email=%s', $userTransferIdAndEan->getEan(), $userTransferIdAndEan->getCustomer()->getEmail());

        try {
            $restResponse = $this->restClient->get($apiMethodUrl);

            return new CustomerInfoResponseItemData($restResponse->getData(), $userTransferIdAndEan);
        } catch (UnexpectedResponseCodeException $exception) {
            $this->changeCustomerPricingGroupUpdatedAt($userTransferIdAndEan->getCustomer());
            $this->logger->addWarning(sprintf('Customer info for User with ean `%s` and email %s not found', $userTransferIdAndEan->getEan(), $userTransferIdAndEan->getCustomer()->getEmail()));
            return null;
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     */
    private function changeCustomerPricingGroupUpdatedAt(User $user): void
    {
        $customerData = $this->customerDataFactory->createFromUser($user);

        /** @var \Shopsys\ShopBundle\Model\Customer\UserData $userData */
        $userData = $customerData->userData;
        $userData->pricingGroupUpdatedAt = new DateTime();

        $customerData->userData = $userData;

        $this->customerFacade->editByCustomer($user->getId(), $customerData);
    }
}

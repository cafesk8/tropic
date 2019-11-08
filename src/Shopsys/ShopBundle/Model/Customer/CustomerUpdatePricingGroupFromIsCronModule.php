<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use Shopsys\ShopBundle\Component\Rest\Exception\UnexpectedResponseCodeException;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferImportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferService;
use Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\CustomerInfoResponseItemData;
use Shopsys\ShopBundle\Model\Order\Status\Transfer\Exception\InvalidOrderStatusTransferResponseItemDataException;

class CustomerUpdatePricingGroupFromIsCronModule extends AbstractTransferImportCronModule
{
    private const TRANSFER_IDENTIFIER = 'import_customers_pricing_groups';

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferService
     */
    private $customerTransferService;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferService $customerTransferService
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        CustomerFacade $customerFacade,
        CustomerTransferService $customerTransferService
    ) {
        parent::__construct($transferCronModuleDependency);

        $this->customerFacade = $customerFacade;
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
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $customers = $this->customerFacade->getForPricingGroupUpdate();
        $allTransferDataItems = [];
        foreach ($customers as $customer) {
            foreach ($customer->getUserTransferIdAndEan() as $transferIdAndEan) {
                try {
                    $allTransferDataItems[] = $this->customerTransferService->getTransferItemsFromResponse($transferIdAndEan);
                } catch (UnexpectedResponseCodeException $unexpectedResponseCodeException) {
                    $this->customerFacade->changeCustomerPricingGroupUpdatedAt($transferIdAndEan->getCustomer());
                    $this->logger->addWarning(sprintf('Customer info for User with ean `%s` and email %s not found', $transferIdAndEan->getEan(), $transferIdAndEan->getCustomer()->getEmail()));
                }
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

        $this->customerFacade->updatePricingGroupByIsResponse(
            $customerInfoResponseItemData->getTransferIdAndEan()->getCustomer()->getPricingGroup(),
            $customerInfoResponseItemData->getCoefficient(),
            $customerInfoResponseItemData->getTransferIdAndEan()
        );
    }

    /**
     * @return bool
     */
    protected function isNextIterationNeeded(): bool
    {
        return true;
    }
}

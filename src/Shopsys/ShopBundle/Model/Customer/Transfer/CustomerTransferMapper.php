<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Transfer;

use Shopsys\FrameworkBundle\Model\Customer\CustomerData;
use Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactory;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;

class CustomerTransferMapper
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactory
     */
    private $customerDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactory $customerDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     */
    public function __construct(
        CustomerDataFactory $customerDataFactory,
        PricingGroupSettingFacade $pricingGroupSettingFacade
    ) {
        $this->customerDataFactory = $customerDataFactory;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferResponseItemData $customerTransferResponseItemData
     * @param \Shopsys\FrameworkBundle\Model\Customer\User $customer
     * @return \Shopsys\FrameworkBundle\Model\Customer\CustomerData
     */
    public function mapTransferDataToCustomerData(
        CustomerTransferResponseItemData $customerTransferResponseItemData,
        ?User $customer
    ): CustomerData {
        if ($customer === null) {
            $customerData = $this->customerDataFactory->create();
        } else {
            $customerData = $this->customerDataFactory->createFromUser($customer);
        }

        /** @var $userData \Shopsys\ShopBundle\Model\Customer\UserData */
        $userData = $customerData->userData;

        $domainId = $customerTransferResponseItemData->getDomainId();
        $userData->transferId = $customerTransferResponseItemData->getDataIdentifier();
        $userData->firstName = $customerTransferResponseItemData->getFirstName();
        $userData->lastName = $customerTransferResponseItemData->getLastName();
        $userData->email = $customerTransferResponseItemData->getEmail();
        $userData->telephone = $customerTransferResponseItemData->getPhone();
        $userData->branchNumber = $customerTransferResponseItemData->getBranchNumber();
        $userData->domainId = $domainId;
        $userData->pricingGroup = $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($domainId);

        $customerData->userData = $userData;

        return $customerData;
    }
}
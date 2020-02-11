<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Command\Migrations\Transfer;

use Shopsys\FrameworkBundle\Model\Customer\CustomerData;
use Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactory;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\ShopBundle\Model\Country\CountryFacade;
use Shopsys\ShopBundle\Model\Customer\DeliveryAddressDataFactory;
use Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferResponseItemData;
use Shopsys\ShopBundle\Model\Customer\User;

class CustomerWithPricingGroupsTransferMapper
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
     * @var \Shopsys\ShopBundle\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\DeliveryAddressDataFactory
     */
    private $deliveryAddressDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerDataFactory $customerDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \Shopsys\ShopBundle\Model\Country\CountryFacade $countryFacade
     * @param \Shopsys\ShopBundle\Model\Customer\DeliveryAddressDataFactory $deliveryAddressDataFactory
     */
    public function __construct(
        CustomerDataFactory $customerDataFactory,
        PricingGroupSettingFacade $pricingGroupSettingFacade,
        CountryFacade $countryFacade,
        DeliveryAddressDataFactory $deliveryAddressDataFactory
    ) {
        $this->customerDataFactory = $customerDataFactory;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
        $this->countryFacade = $countryFacade;
        $this->deliveryAddressDataFactory = $deliveryAddressDataFactory;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\Transfer\CustomerTransferResponseItemData $customerTransferResponseItemData
     * @param \Shopsys\ShopBundle\Model\Customer\User $customer
     * @return \Shopsys\FrameworkBundle\Model\Customer\CustomerData
     */
    public function mapTransferDataToCustomerData(
        CustomerTransferResponseItemData $customerTransferResponseItemData,
        ?User $customer
    ): CustomerData {
        $customerData = $customer === null ?
            $this->customerDataFactory->create() :
            $customerData = $this->customerDataFactory->createFromUser($customer);

        /** @var \Shopsys\ShopBundle\Model\Customer\UserData $userData */
        $userData = $customerData->userData;

        $domainId = $customerTransferResponseItemData->getDomainId();
        $userData->transferId = $customerTransferResponseItemData->getDataIdentifier();
        $userData->firstName = $customerTransferResponseItemData->getFirstName();
        $userData->lastName = $customerTransferResponseItemData->getLastName();
        $userData->email = $customerTransferResponseItemData->getEmail();
        $userData->telephone = $customerTransferResponseItemData->getPhone();
        $userData->domainId = $domainId;
        /** @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup */
        $pricingGroup = $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($domainId);
        $userData->pricingGroup = $pricingGroup;
        $userData->memberOfLoyaltyProgram = false;
        $userData->password = $this->getFakePassword();

        $billingAddressData = $customerData->billingAddressData;
        $billingAddressData->city = $customerTransferResponseItemData->getCity();
        $billingAddressData->street = $customerTransferResponseItemData->getStreet();
        $billingAddressData->postcode = $customerTransferResponseItemData->getPostcode();
        $billingAddressData->country = $this->countryFacade->findByCode($customerTransferResponseItemData->getCountryCode());

        $billingAddressData->companyName = $customerTransferResponseItemData->getCompanyName();
        $billingAddressData->companyNumber = $customerTransferResponseItemData->getCompanyNumber();
        $billingAddressData->companyTaxNumber = $customerTransferResponseItemData->getCompanyTaxNumber();
        if ($billingAddressData->companyNumber !== null) {
            $billingAddressData->companyCustomer = true;
        }

        $deliveryAddressData = $this->deliveryAddressDataFactory->createFromBillingAddressData($billingAddressData);
        $customerData->deliveryAddressData = $deliveryAddressData;

        $customerData->userData = $userData;

        return $customerData;
    }

    /**
     * @return string
     */
    private function getFakePassword(): string
    {
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(128))), 0, 128);
    }
}

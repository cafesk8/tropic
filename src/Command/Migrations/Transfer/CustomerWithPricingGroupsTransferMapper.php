<?php

declare(strict_types=1);

namespace App\Command\Migrations\Transfer;

use App\Model\Country\CountryFacade;
use App\Model\Customer\DeliveryAddressDataFactory;
use  App\Model\Customer\Transfer\CustomerTransferResponseItemData;
use App\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateDataFactory;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;

class CustomerWithPricingGroupsTransferMapper
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateDataFactory
     */
    private $customerUserUpdateDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @var \App\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \App\Model\Customer\DeliveryAddressDataFactory
     */
    private $deliveryAddressDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateDataFactory $customerUserUpdateDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \App\Model\Country\CountryFacade $countryFacade
     * @param \App\Model\Customer\DeliveryAddressDataFactory $deliveryAddressDataFactory
     */
    public function __construct(
        CustomerUserUpdateDataFactory $customerUserUpdateDataFactory,
        PricingGroupSettingFacade $pricingGroupSettingFacade,
        CountryFacade $countryFacade,
        DeliveryAddressDataFactory $deliveryAddressDataFactory
    ) {
        $this->customerUserUpdateDataFactory = $customerUserUpdateDataFactory;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
        $this->countryFacade = $countryFacade;
        $this->deliveryAddressDataFactory = $deliveryAddressDataFactory;
    }

    /**
     * @param \App\Model\Customer\Transfer\CustomerTransferResponseItemData $customerTransferResponseItemData
     * @param \App\Model\Customer\User\CustomerUser $customer
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData
     */
    public function mapTransferDataToCustomerData(
        CustomerTransferResponseItemData $customerTransferResponseItemData,
        ?CustomerUser $customer
    ): CustomerUserUpdateData {
        $customerUserUpdateData = $customer === null ?
            $this->customerUserUpdateDataFactory->create() :
            $customerUserUpdateData = $this->customerUserUpdateDataFactory->createFromCustomerUser($customer);

        /** @var \App\Model\Customer\User\CustomerUserData $customerUserData */
        $customerUserData = $customerUserUpdateData->customerUserData;

        $domainId = $customerTransferResponseItemData->getDomainId();
        $customerUserData->transferId = $customerTransferResponseItemData->getDataIdentifier();
        $customerUserData->firstName = $customerTransferResponseItemData->getFirstName();
        $customerUserData->lastName = $customerTransferResponseItemData->getLastName();
        $customerUserData->email = $customerTransferResponseItemData->getEmail();
        $customerUserData->telephone = $customerTransferResponseItemData->getPhone();
        $customerUserData->domainId = $domainId;
        /** @var \App\Model\Pricing\Group\PricingGroup $pricingGroup */
        $pricingGroup = $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($domainId);
        $customerUserData->pricingGroup = $pricingGroup;
        $customerUserData->memberOfLoyaltyProgram = false;
        $customerUserData->password = $this->getFakePassword();

        $billingAddressData = $customerUserUpdateData->billingAddressData;
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
        $customerUserUpdateData->deliveryAddressData = $deliveryAddressData;

        $customerUserUpdateData->customerUserData = $customerUserData;

        return $customerUserUpdateData;
    }

    /**
     * @return string
     */
    private function getFakePassword(): string
    {
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(128))), 0, 128);
    }
}

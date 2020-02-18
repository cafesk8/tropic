<?php

declare(strict_types=1);

namespace App\Model\Customer\Registration\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\TransferConfig;
use App\Model\Country\CountryFacade;
use App\Model\Customer\User\CustomerUser;
use DateTime;
use Shopsys\FrameworkBundle\Model\Order\OrderNumberSequenceRepository;

class CustomerExportMapper
{
    private const EMPTY_VALUE = 'empty';

    /**
     * @var \App\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\OrderNumberSequenceRepository
     */
    private $orderNumberSequenceRepository;

    /**
     * @param \App\Model\Country\CountryFacade $countryFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderNumberSequenceRepository $orderNumberSequenceRepository
     */
    public function __construct(CountryFacade $countryFacade, OrderNumberSequenceRepository $orderNumberSequenceRepository)
    {
        $this->countryFacade = $countryFacade;
        $this->orderNumberSequenceRepository = $orderNumberSequenceRepository;
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @return array
     */
    public function mapToArray(CustomerUser $customerUser): array
    {
        $orderArray = [];
        $orderArray['Header'] = $this->prepareHeader($customerUser);
        $orderArray['Items'] = [];

        return $orderArray;
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @return array
     */
    private function prepareHeader(CustomerUser $customerUser): array
    {
        $headerArray = [
            'Source' => DomainHelper::DOMAIN_ID_TO_TRANSFER_SOURCE[$customerUser->getDomainId()],
            'Number' => $this->orderNumberSequenceRepository->getNextNumber(),
            'CreatingDateTime' => (new DateTime())->format(TransferConfig::DATETIME_FORMAT),
            'Customer' => [
                'ID' => $customerUser->getTransferId() ?? '',
                'Adress' => $this->mapBillingAddress($customerUser),
                'ICO' => $this->getPassedValueOrEmptyForNull($customerUser->getCustomer()->getBillingAddress()->getCompanyNumber()),
                'DIC' => $this->getPassedValueOrEmptyForNull($customerUser->getCustomer()->getBillingAddress()->getCompanyTaxNumber()),
                'Phone' => $customerUser->getTelephone() ?? '1',
                'Email' => $customerUser->getEmail(),
            ],
            'DeliveryAdress' => $this->mapDeliveryAddress($customerUser),
            'Total' => 0,
            'PaymentMetod' => self::EMPTY_VALUE,
            'ShippingMetod' => self::EMPTY_VALUE,
        ];

        return $headerArray;
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @return array
     */
    private function mapBillingAddress(CustomerUser $customerUser): array
    {
        $billingAddress = $customerUser->getCustomer()->getBillingAddress();

        return [
            'SureName' => $customerUser->getLastName(),
            'ForeName' => $customerUser->getFirstName(),
            'Company' => $this->getPassedValueOrEmptyForNull($billingAddress->getCompanyName()),
            'Street' => $this->getPassedValueOrEmptyForNull($billingAddress->getStreet()),
            'City' => $this->getPassedValueOrEmptyForNull($billingAddress->getCity()),
            'ZIP' => $this->getPassedValueOrEmptyForNull($billingAddress->getPostcode()),
            'Country' => $this->getCountryPropertyContent($customerUser),
        ];
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @return array
     */
    private function mapDeliveryAddress(CustomerUser $customerUser): array
    {
        $deliveryAddress = $customerUser->getDeliveryAddress();

        if ($deliveryAddress === null) {
            return [
                'SureName' => self::EMPTY_VALUE,
                'ForeName' => self::EMPTY_VALUE,
                'Company' => self::EMPTY_VALUE,
                'Street' => self::EMPTY_VALUE,
                'City' => self::EMPTY_VALUE,
                'ZIP' => self::EMPTY_VALUE,
                'Country' => self::EMPTY_VALUE,
                'BranchNumber' => $customerUser->isMemberOfLoyaltyProgram() ? '1' : '0',
            ];
        }

        return [
            'SureName' => $this->getPassedValueOrEmptyForNull($deliveryAddress->getFirstName()),
            'ForeName' => $this->getPassedValueOrEmptyForNull($deliveryAddress->getLastName()),
            'Company' => $this->getPassedValueOrEmptyForNull($deliveryAddress->getCompanyName()),
            'Street' => $this->getPassedValueOrEmptyForNull($deliveryAddress->getStreet()),
            'City' => $this->getPassedValueOrEmptyForNull($deliveryAddress->getCity()),
            'ZIP' => $this->getPassedValueOrEmptyForNull($deliveryAddress->getPostcode()),
            'Country' => $this->getDeliveryAddressCountryPropertyContent($customerUser),
            'BranchNumber' => $customerUser->isMemberOfLoyaltyProgram() ? '1' : '0',
        ];
    }

    /**
     * @param string|null $value
     * @return string
     */
    private function getPassedValueOrEmptyForNull(?string $value): string
    {
        return $value ?? self::EMPTY_VALUE;
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @return string
     */
    private function getCountryPropertyContent(CustomerUser $customerUser): string
    {
        $countryCode = DomainHelper::COUNTRY_CODE_BY_DOMAIN_ID[$customerUser->getDomainId()];

        /** @var \App\Model\Country\Country $country */
        $country = $this->countryFacade->findByCode($countryCode);

        if ($country !== null) {
            if ($country->getExternalId() !== null) {
                return $country->getExternalId();
            } elseif ($country->getCode() !== null) {
                return $country->getCode();
            }
        }

        return '';
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @return string
     */
    private function getDeliveryAddressCountryPropertyContent(CustomerUser $customerUser): string
    {
        $deliveryAddress = $customerUser->getDeliveryAddress();

        if ($deliveryAddress === null || $deliveryAddress->getCountry() === null) {
            return self::EMPTY_VALUE;
        }

        /** @var \App\Model\Country\Country $country */
        $country = $deliveryAddress->getCountry();

        if ($country->getExternalId() !== null) {
            return $country->getExternalId();
        } elseif ($country->getCode() !== null) {
            return $country->getCode();
        }

        return self::EMPTY_VALUE;
    }
}

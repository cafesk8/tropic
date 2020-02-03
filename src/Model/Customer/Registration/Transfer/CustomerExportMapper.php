<?php

declare(strict_types=1);

namespace App\Model\Customer\Registration\Transfer;

use DateTime;
use Shopsys\FrameworkBundle\Model\Order\OrderNumberSequenceRepository;
use App\Component\Domain\DomainHelper;
use App\Component\Transfer\TransferConfig;
use App\Model\Country\CountryFacade;
use App\Model\Customer\User;

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
     * @param \App\Model\Customer\User $user
     * @return array
     */
    public function mapToArray(User $user): array
    {
        $orderArray = [];
        $orderArray['Header'] = $this->prepareHeader($user);
        $orderArray['Items'] = [];

        return $orderArray;
    }

    /**
     * @param \App\Model\Customer\User $user
     * @return array
     */
    private function prepareHeader(User $user): array
    {
        $headerArray = [
            'Source' => DomainHelper::DOMAIN_ID_TO_TRANSFER_SOURCE[$user->getDomainId()],
            'Number' => $this->orderNumberSequenceRepository->getNextNumber(),
            'CreatingDateTime' => (new DateTime())->format(TransferConfig::DATETIME_FORMAT),
            'Customer' => [
                'ID' => $user->getTransferId() ?? '',
                'Adress' => $this->mapBillingAddress($user),
                'ICO' => $this->getPassedValueOrEmptyForNull($user->getBillingAddress()->getCompanyNumber()),
                'DIC' => $this->getPassedValueOrEmptyForNull($user->getBillingAddress()->getCompanyTaxNumber()),
                'Phone' => $user->getTelephone() ?? '1',
                'Email' => $user->getEmail(),
                'IdCards' => [
                    $user->getEan(),
                ],
            ],
            'DeliveryAdress' => $this->mapDeliveryAddress($user),
            'Total' => 0,
            'PaymentMetod' => self::EMPTY_VALUE,
            'ShippingMetod' => self::EMPTY_VALUE,
        ];

        return $headerArray;
    }

    /**
     * @param \App\Model\Customer\User $user
     * @return array
     */
    private function mapBillingAddress(User $user): array
    {
        $billingAddress = $user->getBillingAddress();

        return [
            'SureName' => $user->getLastName(),
            'ForeName' => $user->getFirstName(),
            'Company' => $this->getPassedValueOrEmptyForNull($billingAddress->getCompanyName()),
            'Street' => $this->getPassedValueOrEmptyForNull($billingAddress->getStreet()),
            'City' => $this->getPassedValueOrEmptyForNull($billingAddress->getCity()),
            'ZIP' => $this->getPassedValueOrEmptyForNull($billingAddress->getPostcode()),
            'Country' => $this->getCountryPropertyContent($user),
        ];
    }

    /**
     * @param \App\Model\Customer\User $user
     * @return array
     */
    private function mapDeliveryAddress(User $user): array
    {
        $deliveryAddress = $user->getDeliveryAddress();

        if ($deliveryAddress === null) {
            return [
                'SureName' => self::EMPTY_VALUE,
                'ForeName' => self::EMPTY_VALUE,
                'Company' => self::EMPTY_VALUE,
                'Street' => self::EMPTY_VALUE,
                'City' => self::EMPTY_VALUE,
                'ZIP' => self::EMPTY_VALUE,
                'Country' => self::EMPTY_VALUE,
                'BranchNumber' => $user->isMemberOfLoyaltyProgram() ? '1' : '0',
            ];
        }

        return [
            'SureName' => $this->getPassedValueOrEmptyForNull($deliveryAddress->getFirstName()),
            'ForeName' => $this->getPassedValueOrEmptyForNull($deliveryAddress->getLastName()),
            'Company' => $this->getPassedValueOrEmptyForNull($deliveryAddress->getCompanyName()),
            'Street' => $this->getPassedValueOrEmptyForNull($deliveryAddress->getStreet()),
            'City' => $this->getPassedValueOrEmptyForNull($deliveryAddress->getCity()),
            'ZIP' => $this->getPassedValueOrEmptyForNull($deliveryAddress->getPostcode()),
            'Country' => $this->getDeliveryAddressCountryPropertyContent($user),
            'BranchNumber' => $user->isMemberOfLoyaltyProgram() ? '1' : '0',
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
     * @param \App\Model\Customer\User $user
     * @return string
     */
    private function getCountryPropertyContent(User $user): string
    {
        $countryCode = DomainHelper::COUNTRY_CODE_BY_DOMAIN_ID[$user->getDomainId()];

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
     * @param \App\Model\Customer\User $user
     * @return string
     */
    private function getDeliveryAddressCountryPropertyContent(User $user): string
    {
        $deliveryAddress = $user->getDeliveryAddress();

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

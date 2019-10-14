<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Registration\Transfer;

use DateTime;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Transfer\TransferConfig;
use Shopsys\ShopBundle\Model\Country\CountryFacade;
use Shopsys\ShopBundle\Model\Customer\User;

class CustomerExportMapper
{
    /**
     * @var int
     */
    private $lastNumber;

    private const EMPTY_VALUE = 'empty';

    /**
     * @var \Shopsys\ShopBundle\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Country\CountryFacade $countryFacade
     */
    public function __construct(CountryFacade $countryFacade)
    {
        $this->countryFacade = $countryFacade;

        $this->lastNumber = time();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $user
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
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     * @return array
     */
    private function prepareHeader(User $user): array
    {
        $headerArray = [
            'Source' => DomainHelper::DOMAIN_ID_TO_TRANSFER_SOURCE[$user->getDomainId()],
            'Number' => $this->lastNumber++,
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
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
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
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
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
                'BranchNumber' => $user->isMemberOfBushmanClub() ? '1' : '0',
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
            'BranchNumber' => $user->isMemberOfBushmanClub() ? '1' : '0',
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
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     * @return string
     */
    private function getCountryPropertyContent(User $user): string
    {
        $countryCode = DomainHelper::COUNTRY_CODE_BY_DOMAIN_ID[$user->getDomainId()];

        /** @var \Shopsys\ShopBundle\Model\Country\Country $country */
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
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     * @return string
     */
    private function getDeliveryAddressCountryPropertyContent(User $user): string
    {
        $deliveryAddress = $user->getDeliveryAddress();

        if ($deliveryAddress === null || $deliveryAddress->getCountry() === null) {
            return self::EMPTY_VALUE;
        }

        /** @var \Shopsys\ShopBundle\Model\Country\Country $country */
        $country = $deliveryAddress->getCountry();

        if ($country->getExternalId() !== null) {
            return $country->getExternalId();
        } elseif ($country->getCode() !== null) {
            return $country->getCode();
        }

        return self::EMPTY_VALUE;
    }
}

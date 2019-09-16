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
                'ID' => '',
                'Adress' => [
                    'SureName' => $user->getLastName(),
                    'ForeName' => $user->getFirstName(),
                    'Street' => self::EMPTY_VALUE,
                    'City' => self::EMPTY_VALUE,
                    'ZIP' => self::EMPTY_VALUE,
                    'Country' => $this->getCountryPropertyContent($user),
                ],
                'Phone' => $user->getTelephone() ?? '1',
                'Email' => $user->getEmail(),
                'IdCards' => [
                    $user->getEan(),
                ],
            ],
            'DeliveryAdress' => [
                'BranchNumber' => $user->isMemberOfBushmanClub() ? '1' : '0',
            ],
            'Total' => 0,
            'PaymentMetod' => self::EMPTY_VALUE,
            'ShippingMetod' => self::EMPTY_VALUE,
        ];

        return $headerArray;
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
}

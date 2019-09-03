<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\Registration\Transfer;

use DateTime;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Transfer\TransferConfig;
use Shopsys\ShopBundle\Model\Customer\User;

class CustomerExportMapper
{
    /**
     * @var int
     */
    private $lastNumber;

    private const EMPTY_VALUE = 'empty';

    public function __construct()
    {
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
                ],
                'Phone' => $user->getTelephone() ?? '1',
                'Email' => $user->getEmail(),
                'IdCards' => [
                    $user->getEan(),
                ],
            ],
            'Total' => 0,
            'PaymentMetod' => self::EMPTY_VALUE,
            'ShippingMetod' => self::EMPTY_VALUE,
        ];

        return $headerArray;
    }
}

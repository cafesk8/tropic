<?php

declare(strict_types=1);

namespace App\Model\Customer\User;

use DateTime;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserData as BaseCustomerUserData;

/**
 * @property \App\Model\Pricing\Group\PricingGroup|null $pricingGroup
 * @property \App\Model\Customer\DeliveryAddress|null $defaultDeliveryAddress
 */
class CustomerUserData extends BaseCustomerUserData
{
    /**
     * @var string|null
     */
    public $transferId;

    /**
     * @var string
     */
    public $exportStatus = CustomerUser::EXPORT_NOT_YET;

    /**
     * @var \DateTime
     */
    public $pricingGroupUpdatedAt;

    /**
     * @var int|null
     */
    public $pohodaId;

    public function __construct()
    {
        parent::__construct();

        $this->pricingGroupUpdatedAt = new DateTime();
    }
}

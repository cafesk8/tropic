<?php

declare(strict_types=1);

namespace App\Model\Customer\User;

use DateTime;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserData as BaseCustomerUserData;

/**
 * @property \App\Model\Pricing\Group\PricingGroup|null $pricingGroup
 */
class CustomerUserData extends BaseCustomerUserData
{
    /**
     * @var string|null
     */
    public $transferId;

    /**
     * @var bool
     */
    public $memberOfLoyaltyProgram;

    /**
     * @var string
     */
    public $exportStatus = CustomerUser::EXPORT_NOT_YET;

    /**
     * @var \DateTime
     */
    public $pricingGroupUpdatedAt;

    public function __construct()
    {
        parent::__construct();

        $this->pricingGroupUpdatedAt = new DateTime();
    }
}

<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer;

use DateTime;
use Shopsys\FrameworkBundle\Model\Customer\UserData as BaseUserData;

class UserData extends BaseUserData
{
    /**
     * @var string|null
     */
    public $transferId;

    /**
     * @var string|null
     */
    public $ean;

    /**
     * @var bool
     */
    public $memberOfBushmanClub;

    /**
     * @var string
     */
    public $exportStatus = User::EXPORT_NOT_YET;

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

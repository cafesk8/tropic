<?php

namespace Shopsys\ShopBundle\Model\Customer;

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
    public $branchNumber;

    public function __construct()
    {
        parent::__construct();
    }
}

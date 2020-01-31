<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\TransferIds;

class UserTransferIdData
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User|null
     */
    public $customer;

    /**
     * @var string|null
     */
    public $transferId;
}

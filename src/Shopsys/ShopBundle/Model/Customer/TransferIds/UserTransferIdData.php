<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\TransferIds;

class UserTransferIdData
{
    /**
     * @var \Shopsys\ShopBundle\Model\Customer\User|null
     */
    public $customer;

    /**
     * @var string|null
     */
    public $transferId;
}

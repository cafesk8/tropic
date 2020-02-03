<?php

declare(strict_types=1);

namespace App\Model\Customer\TransferIds;

class UserTransferIdData
{
    /**
     * @var \App\Model\Customer\User|null
     */
    public $customer;

    /**
     * @var string|null
     */
    public $transferId;
}

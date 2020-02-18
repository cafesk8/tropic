<?php

declare(strict_types=1);

namespace App\Model\Customer\TransferIds;

class UserTransferIdFactory
{
    /**
     * @param \App\Model\Customer\TransferIds\UserTransferIdData $userTransferIdData
     * @return \App\Model\Customer\TransferIds\UserTransferId
     */
    public function create(UserTransferIdData $userTransferIdData): UserTransferId
    {
        return new UserTransferId($userTransferIdData);
    }
}

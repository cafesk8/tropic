<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\TransferIds;

class UserTransferIdFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdData $userTransferIdData
     * @return \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferId
     */
    public function create(UserTransferIdData $userTransferIdData): UserTransferId
    {
        return new UserTransferId($userTransferIdData);
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Customer\TransferIds;

use App\Model\Customer\User\CustomerUser;

class UserTransferIdDataFactory
{
    /**
     * @return \App\Model\Customer\TransferIds\UserTransferIdData
     */
    public function create(): UserTransferIdData
    {
        return new UserTransferIdData();
    }

    /**
     * @param \App\Model\Customer\TransferIds\UserTransferId $userTransferId
     * @return \App\Model\Customer\TransferIds\UserTransferIdData
     */
    public function createFromTransferId(UserTransferId $userTransferId): UserTransferIdData
    {
        $userTransferIdData = $this->create();
        $this->fillFromUserTransferId($userTransferIdData, $userTransferId);

        return $userTransferIdData;
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     * @param string $transferId
     * @return \App\Model\Customer\TransferIds\UserTransferIdData
     */
    public function createFromCustomerTransferId(CustomerUser $customerUser, string $transferId): UserTransferIdData
    {
        $userTransferIdData = $this->create();
        $userTransferIdData->customer = $customerUser;
        $userTransferIdData->transferId = $transferId;

        return $userTransferIdData;
    }

    /**
     * @param \App\Model\Customer\TransferIds\UserTransferIdData $userTransferIdData
     * @param \App\Model\Customer\TransferIds\UserTransferId $userTransferId
     */
    protected function fillFromUserTransferId(UserTransferIdData $userTransferIdData, UserTransferId $userTransferId): void
    {
        $userTransferIdData->customer = $userTransferId->getCustomer();
        $userTransferIdData->transferId = $userTransferId->getTransferId();
    }
}

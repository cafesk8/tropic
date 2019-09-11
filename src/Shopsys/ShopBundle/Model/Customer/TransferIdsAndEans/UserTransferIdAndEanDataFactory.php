<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans;

use Shopsys\ShopBundle\Model\Customer\User;

class UserTransferIdAndEanDataFactory
{
    /**
     * @return \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanData
     */
    public function create(): UserTransferIdAndEanData
    {
        return new UserTransferIdAndEanData();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan $userTransferIdAndEan
     * @return \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanData
     */
    public function createFromTransferIdAndEan(UserTransferIdAndEan $userTransferIdAndEan): UserTransferIdAndEanData
    {
        $userTransferIdAndEanData = $this->create();
        $this->fillFromUserTransferIdAndEan($userTransferIdAndEanData, $userTransferIdAndEan);

        return $userTransferIdAndEanData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $customer
     * @param string $transferId
     * @param string $ean
     * @return \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanData
     */
    public function createFromCustomerAndTransferIdAndEan(User $customer, string $transferId, string $ean): UserTransferIdAndEanData
    {
        $userTransferIdAndEanData = $this->create();
        $userTransferIdAndEanData->customer = $customer;
        $userTransferIdAndEanData->transferId = $transferId;
        $userTransferIdAndEanData->ean = $ean;

        return $userTransferIdAndEanData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanData $userTransferIdAndEanData
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan $userTransferIdAndEan
     */
    protected function fillFromUserTransferIdAndEan(UserTransferIdAndEanData $userTransferIdAndEanData, UserTransferIdAndEan $userTransferIdAndEan): void
    {
        $userTransferIdAndEanData->customer = $userTransferIdAndEan->getCustomer();
        $userTransferIdAndEanData->transferId = $userTransferIdAndEan->getTransferId();
        $userTransferIdAndEanData->ean = $userTransferIdAndEan->getEan();
    }
}

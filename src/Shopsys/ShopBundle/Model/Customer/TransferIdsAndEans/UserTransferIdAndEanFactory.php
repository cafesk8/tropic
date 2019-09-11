<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans;

class UserTransferIdAndEanFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanData $userTransferIdAndEanData
     * @return \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEan
     */
    public function create(UserTransferIdAndEanData $userTransferIdAndEanData): UserTransferIdAndEan
    {
        return new UserTransferIdAndEan($userTransferIdAndEanData);
    }
}

<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans;

class UserTransferIdAndEanData
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User|null
     */
    public $customer;

    /**
     * @var string|null
     */
    public $transferId;

    /**
     * @var string|null
     */
    public $ean;
}

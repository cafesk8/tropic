<?php

declare(strict_types=1);

namespace App\Model\Customer\TransferIds;

use Doctrine\ORM\Mapping as ORM;
use App\Model\Customer\User\CustomerUser;

/**
 * @ORM\Table(name="user_transfer_ids")
 * @ORM\Entity
 */
class UserTransferId
{
    /**
     * @var \App\Model\Customer\User\CustomerUser
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Customer\User\CustomerUser", inversedBy="userTransferId")
     * @ORM\JoinColumn(nullable=false, name="customer_user_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     */
    private $customer;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    private $transferId;

    /**
     * @param \App\Model\Customer\TransferIds\UserTransferIdData $userTransferIdData
     */
    public function __construct(UserTransferIdData $userTransferIdData)
    {
        $this->customer = $userTransferIdData->customer;
        $this->transferId = $userTransferIdData->transferId;
    }

    /**
     * @return \App\Model\Customer\User\CustomerUser
     */
    public function getCustomer(): CustomerUser
    {
        return $this->customer;
    }

    /**
     * @return string
     */
    public function getTransferId(): string
    {
        return $this->transferId;
    }
}

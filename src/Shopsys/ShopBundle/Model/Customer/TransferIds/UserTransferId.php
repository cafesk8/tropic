<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\TransferIds;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\ShopBundle\Model\Customer\User;

/**
 * @ORM\Table(name="user_transfer_ids")
 * @ORM\Entity
 */
class UserTransferId
{
    /**
     * @var \Shopsys\ShopBundle\Model\Customer\User
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Customer\User", inversedBy="userTransferId")
     * @ORM\JoinColumn(nullable=false, name="customer_id", referencedColumnName="id", onDelete="CASCADE")
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
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIds\UserTransferIdData $userTransferIdData
     */
    public function __construct(UserTransferIdData $userTransferIdData)
    {
        $this->customer = $userTransferIdData->customer;
        $this->transferId = $userTransferIdData->transferId;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Customer\User
     */
    public function getCustomer(): User
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

<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\ShopBundle\Model\Customer\User;

/**
 * @ORM\Table(name="user_transfer_ids_and_eans")
 * @ORM\Entity
 */
class UserTransferIdAndEan
{
    /**
     * @var \Shopsys\ShopBundle\Model\Customer\User
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Customer\User", inversedBy="userTransferIdAndEan")
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
     * @var string
     *
     * @ORM\Column(type="string", length=13)
     * @ORM\Id
     */
    private $ean;

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\TransferIdsAndEans\UserTransferIdAndEanData $userTransferIdAndEanData
     */
    public function __construct(UserTransferIdAndEanData $userTransferIdAndEanData)
    {
        $this->customer = $userTransferIdAndEanData->customer;
        $this->transferId = $userTransferIdAndEanData->transferId;
        $this->ean = $userTransferIdAndEanData->ean;
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

    /**
     * @return string
     */
    public function getEan(): string
    {
        return $this->ean;
    }
}

<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transfer\Issue;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\ShopBundle\Model\Transfer\Transfer;

/**
 * @ORM\Table(name="transfer_issues", indexes={@ORM\Index(columns={"group_id"}), @ORM\Index(columns={"message"})})
 * @ORM\Entity
 */
class TransferIssue
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Shopsys\ShopBundle\Model\Transfer\Transfer
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Transfer\Transfer")
     * @ORM\JoinColumn(name="transfer_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $transfer;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private $message;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $context;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private $groupId;

    /**
     * @param \Shopsys\ShopBundle\Model\Transfer\Transfer $transfer
     * @param \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueData $transferIssueData
     */
    public function __construct(Transfer $transfer, TransferIssueData $transferIssueData)
    {
        $this->transfer = $transfer;
        $this->message = $transferIssueData->message;
        $this->createdAt = new \DateTime();
        $this->groupId = $transferIssueData->groupId;
        $this->context = $transferIssueData->context;
    }
}

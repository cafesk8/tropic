<?php

declare(strict_types=1);

namespace App\Model\Transfer\Issue;

use Doctrine\ORM\Mapping as ORM;
use App\Model\Transfer\Transfer;

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
     * @var \App\Model\Transfer\Transfer
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Transfer\Transfer")
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
     * @param \App\Model\Transfer\Transfer $transfer
     * @param \App\Model\Transfer\Issue\TransferIssueData $transferIssueData
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

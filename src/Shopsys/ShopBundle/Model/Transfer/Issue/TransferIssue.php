<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transfer\Issue;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\ShopBundle\Model\Transfer\Transfer;

/**
 * @ORM\Table(name="transfer_issues")
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
     * @param \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueData $transferIssueData
     */
    public function __construct(TransferIssueData $transferIssueData)
    {
        $this->transfer = $transferIssueData->transfer;
        $this->message = $transferIssueData->message;
        $this->createdAt = new \DateTime();
    }
}

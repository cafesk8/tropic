<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transfer\Issue;

use Doctrine\ORM\EntityManagerInterface;

class TransferIssueFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueData[] $transferIssuesData
     */
    public function createMultiple(array $transferIssuesData): void
    {
        $toFlush = [];
        foreach ($transferIssuesData as $transferIssueData) {
            $transferIssue = new TransferIssue($transferIssueData);
            $this->em->persist($transferIssue);
            $toFlush[] = $transferIssue;
        }
        if (!empty($toFlush)) {
            $this->em->flush($toFlush);
        }
    }
}

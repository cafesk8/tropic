<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transfer\Issue;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class TransferIssueFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueRepository
     */
    private $transferIssueRepository;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueRepository $transferIssueRepository
     */
    public function __construct(EntityManagerInterface $em, TransferIssueRepository $transferIssueRepository)
    {
        $this->em = $em;
        $this->transferIssueRepository = $transferIssueRepository;
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

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getTransferIssuesQueryBuilderForDataGrid(): QueryBuilder
    {
        return $this->transferIssueRepository->getTransferIssuesQueryBuilderForDataGrid();
    }
}

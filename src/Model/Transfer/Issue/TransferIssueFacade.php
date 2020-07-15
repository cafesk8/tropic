<?php

declare(strict_types=1);

namespace App\Model\Transfer\Issue;

use App\Model\Administrator\Administrator;
use App\Model\Transfer\TransferFacade;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class TransferIssueFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    protected $em;

    /**
     * @var \App\Model\Transfer\Issue\TransferIssueRepository
     */
    private $transferIssueRepository;

    /**
     * @var \App\Model\Transfer\TransferFacade
     */
    private $transferFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     * @param \App\Model\Transfer\Issue\TransferIssueRepository $transferIssueRepository
     * @param \App\Model\Transfer\TransferFacade $transferFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        TransferIssueRepository $transferIssueRepository,
        TransferFacade $transferFacade
    ) {
        $this->em = $em;
        $this->transferIssueRepository = $transferIssueRepository;
        $this->transferFacade = $transferFacade;
    }

    /**
     * @param \App\Model\Transfer\Issue\TransferIssueData[] $transferIssuesData
     */
    public function createMultiple(array $transferIssuesData): void
    {
        $toFlush = [];
        $transfersByTransferIdentifier = [];
        foreach ($transferIssuesData as $transferIssueData) {
            $transferIdentifier = $transferIssueData->transferIdentifier;
            if (isset($transfersByTransferIdentifier[$transferIdentifier])) {
                $transfer = $transfersByTransferIdentifier[$transferIdentifier];
            } else {
                $transfer = $this->transferFacade->getByIdentifier($transferIdentifier);
                $transfersByTransferIdentifier[$transferIdentifier] = $transfer;
            }
            $transferIssue = new TransferIssue($transfer, $transferIssueData);
            $this->em->persist($transferIssue);
            $toFlush[] = $transferIssue;
        }
        if (!empty($toFlush)) {
            $this->em->flush($toFlush);
        }

        $this->transferIssueRepository->deleteExcessiveTransferIssues();
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getTransferIssuesQueryBuilderForDataGrid(): QueryBuilder
    {
        return $this->transferIssueRepository->getTransferIssuesQueryBuilderForDataGrid();
    }

    /**
     * @param \App\Model\Administrator\Administrator $administrator
     */
    public function logTransferIssuesVisitByAdministrator(Administrator $administrator): void
    {
        $administrator->setLastTransferIssuesVisit(new DateTime());
        $this->em->flush($administrator);
    }

    /**
     * @param \App\Model\Administrator\Administrator $administrator
     * @return int
     */
    public function getUnseenTransferIssuesCount(Administrator $administrator): int
    {
        return $this->transferIssueRepository->getUnseenTransferIssuesCount($administrator);
    }

    /**
     * @param string $groupId
     * @param string $message
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getTransferIssuesWithContextByGroupIdAndMessageQueryBuilderForDataGrid(string $groupId, string $message): QueryBuilder
    {
        return $this->transferIssueRepository->getTransferIssuesWithContextByGroupIdAndMessageQueryBuilderForDataGrid($groupId, $message);
    }
}

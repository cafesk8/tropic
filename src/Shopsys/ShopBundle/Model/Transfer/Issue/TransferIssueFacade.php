<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transfer\Issue;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Shopsys\ShopBundle\Model\Administrator\Administrator;
use Shopsys\ShopBundle\Model\Transfer\TransferFacade;

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
     * @var \Shopsys\ShopBundle\Model\Transfer\TransferFacade
     */
    private $transferFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueRepository $transferIssueRepository
     * @param \Shopsys\ShopBundle\Model\Transfer\TransferFacade $transferFacade
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
     * @param \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueData[] $transferIssuesData
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
     * @param \Shopsys\ShopBundle\Model\Administrator\Administrator $administrator
     */
    public function logTransferIssuesVisitByAdministrator(Administrator $administrator): void
    {
        $administrator->setLastTransferIssuesVisit(new DateTime());
        $this->em->flush($administrator);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Administrator\Administrator $administrator
     * @return int
     */
    public function getUnseenTransferIssuesCount(Administrator $administrator): int
    {
        return $this->transferIssueRepository->getUnseenTransferIssuesCount($administrator);
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Transfer\Issue;

use App\Model\Administrator\Administrator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;

class TransferIssueRepository
{
    public const LIMIT_TRANSFER_ISSUES_COUNT = 100000;

    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $em;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getTransferIssuesQueryBuilderForDataGrid(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('ti.message AS message, COUNT(ti.id) AS count, MAX(ti.groupId) as groupId, MAX(ti.id) AS id, MAX(ti.createdAt) AS createdAt, COUNT(ti.context) AS contextCount, MAX(t.name) AS transferName, MAX(t.identifier) AS transferIdentifier')
            ->from(TransferIssue::class, 'ti')
            ->join('ti.transfer', 't')
            ->orderBy('MAX(ti.createdAt)', 'DESC')
            ->addOrderBy('MAX(ti.id)', 'DESC')
            ->groupBy('ti.message, t.id, ti.groupId');
    }

    public function deleteExcessiveTransferIssues(): void
    {
        $this->em->createNativeQuery(
            'DELETE FROM transfer_issues 
            WHERE id IN (
                SELECT id 
                FROM transfer_issues 
                ORDER BY created_at DESC
                OFFSET :limitTransferIssuesCount
            )',
            new ResultSetMapping()
        )->setParameter('limitTransferIssuesCount', self::LIMIT_TRANSFER_ISSUES_COUNT)->execute();
    }

    /**
     * @param \App\Model\Administrator\Administrator $administrator
     * @return int
     */
    public function getUnseenTransferIssuesCount(Administrator $administrator): int
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('COUNT(ti)')
            ->from(TransferIssue::class, 'ti');

        $lastTransferIssuesVisit = $administrator->getLastTransferIssuesVisit();
        if ($lastTransferIssuesVisit !== null) {
            $queryBuilder->andWhere('ti.createdAt >= :lastTransferIssuesVisit')
                ->setParameter('lastTransferIssuesVisit', $lastTransferIssuesVisit);
        }

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string $groupId
     * @param string $message
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getTransferIssuesWithContextByGroupIdAndMessageQueryBuilderForDataGrid(
        string $groupId,
        string $message
    ): QueryBuilder {
        return $this->em->createQueryBuilder()
            ->select('ti')
            ->from(TransferIssue::class, 'ti')
            ->where('ti.context IS NOT NULL')
            ->andWhere('ti.groupId = :groupId')
            ->andWhere('ti.message = :message')
            ->orderBy('ti.createdAt', 'DESC')
            ->setParameter('groupId', $groupId)
            ->setParameter('message', $message);
    }
}

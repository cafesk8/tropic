<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transfer\Issue;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Shopsys\ShopBundle\Model\Administrator\Administrator;

class TransferIssueRepository
{
    public const LIMIT_TRANSFER_ISSUES_COUNT = 200000;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
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

    public function deleteExcessiveTransferIssues()
    {
        $oldIssues = $this->em
            ->createQueryBuilder()
            ->select('ti')
            ->from(TransferIssue::class, 'ti')
            ->setFirstResult(self::LIMIT_TRANSFER_ISSUES_COUNT)
            ->orderBy('ti.createdAt', 'DESC')
            ->getQuery()
            ->execute();

        foreach ($oldIssues as $oldIssue) {
            $this->em->remove($oldIssue);
        }
        $this->em->flush($oldIssues);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Administrator\Administrator $administrator
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
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getTransferIssuesWithContextByGroupIdQueryBuilderForDataGrid(string $groupId): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('ti')
            ->from(TransferIssue::class, 'ti')
            ->where('ti.context IS NOT NULL')
            ->andWhere('ti.groupId = :groupId')
            ->orderBy('ti.createdAt', 'DESC')
            ->setParameter('groupId', $groupId);
    }
}

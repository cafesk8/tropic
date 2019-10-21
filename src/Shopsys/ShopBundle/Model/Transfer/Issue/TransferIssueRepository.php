<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transfer\Issue;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class TransferIssueRepository
{
    public const LIMIT_TRANSFER_ISSUES_COUNT = 20000;

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
            ->select('ti, t')
            ->from(TransferIssue::class, 'ti')
            ->join('ti.transfer', 't')
            ->orderBy('ti.createdAt', 'DESC')
            ->addOrderBy('ti.id', 'DESC')
            ->setMaxResults(self::LIMIT_TRANSFER_ISSUES_COUNT);
    }
}

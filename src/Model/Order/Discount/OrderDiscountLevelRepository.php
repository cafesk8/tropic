<?php

declare(strict_types=1);

namespace App\Model\Order\Discount;

use App\Model\Order\Exception\OrderDiscountLevelNotFoundException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Money\Money;

class OrderDiscountLevelRepository
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    protected $entityManager;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    private function getOrderDiscountLevelRepository(): ObjectRepository
    {
        return $this->entityManager->getRepository(OrderDiscountLevel::class);
    }

    /**
     * @param int $id
     * @return \App\Model\Order\Discount\OrderDiscountLevel
     */
    public function getById(int $id): OrderDiscountLevel
    {
        $orderDiscountLevel = $this->findById($id);
        if ($orderDiscountLevel === null) {
            throw new OrderDiscountLevelNotFoundException();
        }

        return $orderDiscountLevel;
    }

    /**
     * @param int $id
     * @return \App\Model\Order\Discount\OrderDiscountLevel|null
     */
    private function findById(int $id): ?OrderDiscountLevel
    {
        return $this->getOrderDiscountLevelRepository()->find($id);
    }

    /**
     * @param int $domainId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderForAdminOrderDiscountLevelGrid(int $domainId): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('odl.id as id, odl.enabled as enabled, odl.discountPercent as discountPercent, odl.priceLevelWithVat as priceLevelWithVat')
            ->from(OrderDiscountLevel::class, 'odl')
            ->where('odl.domainId = :domainId')
            ->setParameter('domainId', $domainId)
            ->groupBy('id')
            ->orderBy('priceLevelWithVat', 'ASC');
    }

    /**
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $exceptLevel
     * @return \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public function getAllLevelsOnDomainExceptLevel(int $domainId, ?Money $exceptLevel = null): array
    {
        $queryBuilder = $this->getAllLevelsOnDomainQueryBuilder($domainId);
        if ($exceptLevel !== null) {
            $queryBuilder
                ->andWhere('odl.priceLevelWithVat != :exceptLevel')
                ->setParameter('exceptLevel', $exceptLevel->getAmount());
        }

        return array_column($queryBuilder->getQuery()->getResult(), 'priceLevelWithVat');
    }

    /**
     * @param int $domainId
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getAllLevelsOnDomainQueryBuilder(int $domainId): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('odl.priceLevelWithVat')
            ->from(OrderDiscountLevel::class, 'odl')
            ->where('odl.domainId = :domainId')
            ->setParameter('domainId', $domainId)
            ->orderBy('odl.priceLevelWithVat');
    }
}

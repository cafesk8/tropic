<?php

declare(strict_types=1);

namespace App\Model\Order\Gift;

use App\Model\Order\Exception\OrderGiftNotFoundException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class OrderGiftRepository
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
    private function getOrderGiftRepository(): ObjectRepository
    {
        return $this->entityManager->getRepository(OrderGift::class);
    }

    /**
     * @param int $id
     * @return \App\Model\Order\Gift\OrderGift
     */
    public function getById(int $id): OrderGift
    {
        $orderGift = $this->findById($id);
        if ($orderGift === null) {
            throw new OrderGiftNotFoundException();
        }

        return $orderGift;
    }

    /**
     * @param int $id
     * @return \App\Model\Order\Gift\OrderGift|null
     */
    private function findById(int $id): ?OrderGift
    {
        return $this->getOrderGiftRepository()->find($id);
    }

    /**
     * @param int $domainId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderForAdminOrderGiftGrid(int $domainId): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('og.id as id, og.enabled as enabled, COUNT(p) AS productsCount, og.priceLevelWithVat as priceLevelWithVat')
            ->from(OrderGift::class, 'og')
            ->join('og.products', 'p')
            ->where('og.domainId = :domainId')
            ->setParameter('domainId', $domainId)
            ->groupBy('id')
            ->orderBy('priceLevelWithVat', 'ASC');
    }
}

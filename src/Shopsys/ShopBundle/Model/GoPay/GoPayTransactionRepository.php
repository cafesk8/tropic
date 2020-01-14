<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\GoPay;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use GoPay\Definition\Response\PaymentStatus;
use Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder;
use Shopsys\ShopBundle\Model\Order\Order;

class GoPayTransactionRepository
{
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
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getGoPayTransactionRepository(): ObjectRepository
    {
        return $this->em->getRepository(GoPayTransaction::class);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return \Shopsys\ShopBundle\Model\GoPay\GoPayTransaction[]
     */
    public function findAllByOrder(Order $order): array
    {
        return $this->getGoPayTransactionRepository()->findBy(['order' => $order]);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return bool
     */
    public function isOrderPaid(Order $order): bool
    {
        $queryBuilder = $this->getAllQueryBuilder();

        $queryBuilder
            ->select('COUNT(gpt)')
            ->where('gpt.order = :order')
            ->andWhere('gpt.goPayStatus = :status')
            ->setParameter('status', PaymentStatus::PAID)
            ->setParameter('order', $order);

        return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder
     */
    public function getAllQueryBuilder(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('gpt')
            ->from(GoPayTransaction::class, 'gpt');
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return \Shopsys\ShopBundle\Model\GoPay\GoPayTransaction|null
     */
    public function getLastTransactionByOrder(Order $order): ?GoPayTransaction
    {
        $queryBuilder = $this->getAllQueryBuilder();

        $queryBuilder
            ->where('gpt.order = :order')
            ->orderBy('gpt.goPayId', 'DESC')
            ->setParameter('order', $order)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}

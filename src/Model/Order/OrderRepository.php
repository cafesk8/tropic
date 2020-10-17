<?php

declare(strict_types=1);

namespace App\Model\Order;

use App\Model\GoPay\GoPayTransaction;
use App\Model\Order\Status\OrderStatus;
use App\Model\Payment\Payment;
use App\Model\PayPal\PayPalFacade;
use DateTime;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use GoPay\Definition\Response\PaymentStatus;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Order\Exception\OrderNotFoundException;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem;
use Shopsys\FrameworkBundle\Model\Order\OrderRepository as BaseOrderRepository;

/**
 * @method \App\Model\Order\Order[] getOrdersByUserId(int $userId)
 * @method \App\Model\Order\Order|null findLastByUserId(int $userId)
 * @method \App\Model\Order\Order|null findById(int $id)
 * @method \App\Model\Order\Order getById(int $id)
 * @method bool isOrderStatusUsed(\App\Model\Order\Status\OrderStatus $orderStatus)
 * @method \App\Model\Order\Order[] getCustomerUserOrderList(\App\Model\Customer\User\CustomerUser $customerUser)
 * @method \App\Model\Order\Order[] getOrderListForEmailByDomainId(string $email, int $domainId)
 * @method \App\Model\Order\Order getByUrlHashAndDomain(string $urlHash, int $domainId)
 * @method \App\Model\Order\Order getByOrderNumberAndUser(string $orderNumber, \App\Model\Customer\User\CustomerUser $customerUser)
 * @method \App\Model\Order\Order|null findByUrlHashIncludingDeletedOrders(string $urlHash)
 * @method \App\Model\Pricing\Currency\Currency[] getCurrenciesUsedInOrders()
 * @method \App\Model\Order\Order[] getOrdersByCustomerUserId(int $customerUserId)
 * @method \App\Model\Order\Order|null findLastByCustomerUserId(int $customerUserId)
 * @method \App\Model\Order\Order getByOrderNumberAndCustomerUser(string $orderNumber, \App\Model\Customer\User\CustomerUser $customerUser)
 */
class OrderRepository extends BaseOrderRepository
{
    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getOrderItemRepository(): EntityRepository
    {
        return $this->em->getRepository(OrderItem::class);
    }

    /**
     * @param \DateTime $fromDate
     * @return \App\Model\Order\Order[]
     */
    public function getAllUnpaidGoPayOrders(\DateTime $fromDate): array
    {
        $queryBuilder = $this->createOrderQueryBuilder()
            ->join(Payment::class, 'p', Join::WITH, 'o.payment = p.id')
            ->join(GoPayTransaction::class, 'gpt', Join::WITH, 'o.id = gpt.order AND gpt.goPayStatus != :statusPaid')
            ->andWhere('p.type = :type')
            ->andWhere('o.createdAt >= :fromDate')
            ->orderBy('o.createdAt', 'ASC')
            ->setParameter('fromDate', $fromDate)
            ->setParameter('statusPaid', PaymentStatus::PAID)
            ->setParameter(':type', Payment::TYPE_GOPAY);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \DateTime $fromDate
     * @return \App\Model\Order\Order[]
     */
    public function getAllUnpaidPayPalOrders(\DateTime $fromDate): array
    {
        $queryBuilder = $this->createOrderQueryBuilder()
            ->join(Payment::class, 'p', Join::WITH, 'o.payment = p.id')
            ->andWhere('p.type = :paymentType AND (o.payPalStatus != :statusPaid OR o.payPalStatus IS NULL)')
            ->andWhere('o.payPalId IS NOT NULL')
            ->andWhere('o.createdAt >= :fromDate')
            ->orderBy('o.createdAt', 'ASC')
            ->setParameter('fromDate', $fromDate)
            ->setParameter('statusPaid', PayPalFacade::PAYMENT_APPROVED)
            ->setParameter(':paymentType', Payment::TYPE_PAY_PAL);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param int $customerId
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return float
     */
    public function getOrderProductsTotalPriceByCustomerAndDatePeriod(int $customerId, DateTime $dateFrom, DateTime $dateTo): float
    {
        $queryBuilder = $this->createOrderQueryBuilder()
            ->select('SUM(o.totalProductPriceWithVat) AS sum')
            ->where('o.customerUser = :customerId')
            ->andWhere('o.createdAt >= :ordersFrom')
            ->andWhere('o.createdAt <= :ordersTo')
            ->andWhere('o.status = :status')
            ->andWhere('o.deleted = FALSE')
            ->setParameters([
                'customerId' => $customerId,
                'ordersFrom' => $dateFrom,
                'ordersTo' => $dateTo,
                'status' => OrderStatus::TYPE_DONE,
            ]);

        return (float)$queryBuilder->getQuery()->getSingleResult()['sum'] ?? 0;
    }

    /**
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @return int[]
     */
    public function getCustomerIdsFromOrdersByDatePeriod(DateTime $startTime, DateTime $endTime): array
    {
        $queryBuilder = $this->createOrderQueryBuilder()
            ->select('IDENTITY(o.customer) as id')
            ->join(CustomerUser::class, 'u', Join::WITH, 'o.customer = u.id')
            ->where('o.createdAt > :startTime')
            ->andWhere('o.createdAt < :endTime')
            ->andWhere('o.customer is not null')
            ->groupBy('o.customer')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        $customerIds = $queryBuilder->getQuery()->getResult();

        return array_column($customerIds, 'id');
    }

    /**
     * @param int[] $customerIds
     * @param \DateTime $endTime
     * @return \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public function getOrdersValueIndexedByCustomerIdOlderThanDate(array $customerIds, DateTime $endTime): array
    {
        $queryBuilder = $this->createOrderQueryBuilder()
            ->select('IDENTITY(o.customer) as customerId')
            ->addSelect('SUM(o.totalPriceWithVat) as ordersValue')
            ->where('o.status = :statusCompleted')
            ->andWhere('o.customer IN (:customerIds)')
            ->andWhere('o.deleted = FALSE')
            ->andWhere('o.createdAt < :endTime')
            ->groupBy('customerId')
            ->setParameter('statusCompleted', OrderStatus::TYPE_DONE)
            ->setParameter('customerIds', $customerIds)
            ->setParameter('endTime', $endTime);

        $ordersValue = $queryBuilder->getQuery()->getResult();

        $ordersValueIndexedByCustomer = array_column($ordersValue, 'ordersValue', 'customerId');

        $ordersValueForAllGivenCustomerIds = [];

        foreach ($customerIds as $customerId) {
            $ordersValueForAllGivenCustomerIds[$customerId] = array_key_exists($customerId, $ordersValueIndexedByCustomer) ? $ordersValueIndexedByCustomer[$customerId] : 0;
        }

        return array_map(function ($value) {
            return Money::create($value);
        }, $ordersValueForAllGivenCustomerIds);
    }

    /**
     * @param int $limit
     * @return \App\Model\Order\Order[]
     */
    public function getReadyOrdersForExportBatch(int $limit): array
    {
        /**
         * not registrated customer: order is exported without waiting for (not existent) customer ID from IS
         * registrated customer: order is exported after import of customer ID from IS
         */

        return $this->createOrderQueryBuilder()
            ->leftJoin(GoPayTransaction::class, 'gpt', Join::WITH, 'o.id = gpt.order')
            ->andWhere('o.exportStatus = :exportStatus')
            ->andWhere('o.customer IS NULL OR c.transferId IS NOT NULL')
            ->andWhere('(p.type = :paymentTypeGoPay AND gpt.goPayStatus = :goPayStatusPaid) OR p.type != :paymentTypeGoPay')
            ->leftJoin('o.customer', 'c')
            ->leftJoin('o.payment', 'p')
            ->groupBy('o.id')
            ->setMaxResults($limit)
            ->setParameters([
                'exportStatus' => Order::EXPORT_NOT_YET,
                'goPayStatusPaid' => PaymentStatus::PAID,
                'paymentTypeGoPay' => Payment::TYPE_GOPAY,
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $limit
     * @return \App\Model\Order\Order[]
     */
    public function getBatchToCheckOrderStatus(int $limit): array
    {
        $queryBuilder = $this->createOrderQueryBuilder()
            ->join('o.status', 'os')
            ->where('o.exportStatus = :exportStatus')
            ->andWhere('os.type NOT IN (:orderStatuses)')
            ->andWhere('o.statusCheckedAt < :dateTime')
            ->orderBy('o.statusCheckedAt', 'ASC')
            ->addOrderBy('o.id', 'ASC')
            ->setMaxResults($limit);

        $queryBuilder->setParameters([
            'exportStatus' => Order::EXPORT_SUCCESS,
            'orderStatuses' => [OrderStatus::TYPE_DONE, OrderStatus::TYPE_CANCELED],
            'dateTime' => new DateTime('-5 minutes'),
        ]);

        return $queryBuilder->getQuery()
            ->getResult();
    }

    /**
     * @param string $number
     * @return \App\Model\Order\Order|null
     */
    public function findByNumber(string $number): ?Order
    {
        return $this->getOrderRepository()->findOneBy(['number' => $number]);
    }

    /**
     * @param string $number
     * @return \App\Model\Order\Order
     */
    public function getByNumber(string $number): Order
    {
        $order = $this->findByNumber($number);
        if ($order === null) {
            throw new OrderNotFoundException(sprintf('Order with number `%s` not found', $number));
        }

        return $order;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param string $ean
     * @return \App\Model\Order\Item\OrderItem[]
     */
    public function findOrderItemsByEan(Order $order, string $ean): array
    {
        return $this->getOrderItemRepository()->findBy([
            'order' => $order,
            'ean' => $ean,
        ]);
    }

    /**
     * @return \App\Model\Order\Order[]
     */
    public function findAll(): array
    {
        return $this->getOrderRepository()->findAll();
    }

    /**
     * @param string $email
     * @param int $domainId
     * @return \App\Model\Order\Order|null
     */
    public function findNewestByEmailAndDomainId(string $email, int $domainId): ?Order
    {
        return $this->getOrderRepository()->findOneBy([
            'email' => $email,
            'domainId' => $domainId,
        ], [
            'createdAt' => 'DESC',
        ]);
    }

    /**
     * @param int $limit
     * @return \App\Model\Order\Order[]
     */
    public function getForTransfer(int $limit): array
    {
        $queryBuilder = $this->createOrderQueryBuilder()
            ->andWhere('o.exportStatus = :exportStatus')
            ->orderBy('o.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->setParameters([
                'exportStatus' => Order::EXPORT_NOT_YET,
            ]);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return \App\Model\Order\Order[]
     */
    public function getAllForExportToZbozi(): array
    {
        $queryBuilder = $this->createOrderQueryBuilder()
            ->andWhere('o.exportZboziStatus = :exportZboziStatus')
            ->orderBy('o.createdAt', 'ASC')
            ->setParameters([
                'exportZboziStatus' => Order::EXPORT_ZBOZI_NOT_YET,
            ]);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int[] $orderIds
     */
    public function markOrdersAsExportedToZbozi(array $orderIds): void
    {
        $this->em->createNativeQuery('
            UPDATE orders
            SET export_zbozi_status = :exported
            WHERE id IN (:orderIds)', new ResultSetMapping()
        )->setParameters([
            'exported' => Order::EXPORT_ZBOZI_DONE,
            'orderIds' => $orderIds,
        ])->execute();
    }

    /**
     * @param int $pohodaId
     * @return \App\Model\Order\Order|null
     */
    public function findByPohodaId(int $pohodaId): ?Order
    {
        return $this->getOrderRepository()->findOneBy(['pohodaId' => $pohodaId]);
    }

    /**
     * @param int $legacyId
     * @return \App\Model\Order\Order|null
     */
    public function findByLegacyId(int $legacyId): ?Order
    {
        return $this->getOrderRepository()->findOneBy(['legacyId' => $legacyId]);
    }

    /**
     * @param \DateTime $fromDate
     * @return \App\Model\Order\Order[]
     */
    public function getOrdersWithLegacyIdAndWithoutPohodaIdFromDate(DateTime $fromDate): array
    {
        $queryBuilder = $this->createOrderQueryBuilder()
            ->where('o.pohodaId IS NULL')
            ->andWhere('o.legacyId IS NOT NULL')
            ->andWhere('o.createdAt >= :fromDate')
            ->setParameter('fromDate', $fromDate);

        return $queryBuilder->getQuery()->getResult();
    }
}

<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use DateTime;
use Doctrine\ORM\Query\Expr\Join;
use GoPay\Definition\Response\PaymentStatus;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Order\Exception\OrderNotFoundException;
use Shopsys\FrameworkBundle\Model\Order\OrderRepository as BaseOrderRepository;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatus;
use Shopsys\ShopBundle\Model\Payment\Payment;
use Shopsys\ShopBundle\Model\PayPal\PayPalFacade;

class OrderRepository extends BaseOrderRepository
{
    /**
     * @param \DateTime $fromDate
     * @return \Shopsys\ShopBundle\Model\Order\Order[]
     */
    public function getAllUnpaidGoPayOrders(\DateTime $fromDate): array
    {
        $queryBuilder = $this->createOrderQueryBuilder()
            ->join(Payment::class, 'p', Join::WITH, 'o.payment = p.id')
            ->andWhere('p.type = :type AND (o.goPayStatus != :statusPaid OR o.goPayStatus IS NULL)')
            ->andWhere('o.goPayId IS NOT NULL')
            ->andWhere('o.createdAt >= :fromDate')
            ->orderBy('o.createdAt', 'ASC')
            ->setParameter('fromDate', $fromDate)
            ->setParameter('statusPaid', PaymentStatus::PAID)
            ->setParameter(':type', Payment::TYPE_GOPAY);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \DateTime $fromDate
     * @return \Shopsys\ShopBundle\Model\Order\Order[]
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
            ->where('o.customer = :customerId')
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
            ->join(User::class, 'u', Join::WITH, 'o.customer = u.id')
            ->where('o.createdAt > :startTime')
            ->andWhere('o.createdAt < :endTime')
            ->andWhere('o.customer is not null')
            ->andWhere('u.memberOfBushmanClub = TRUE')
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
     * @return \Shopsys\ShopBundle\Model\Order\Order[]
     */
    public function getNotExportedOrdersBatch(int $limit): array
    {
        return $this->createOrderQueryBuilder()
            ->andWhere('o.exportStatus = :exportStatus')
            ->setParameter('exportStatus', Order::EXPORT_NOT_YET)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Order\Order[]
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
     * @return \Shopsys\ShopBundle\Model\Order\Order|null
     */
    public function findByNumber(string $number): ?Order
    {
        return $this->getOrderRepository()->findOneBy(['number' => $number]);
    }

    /**
     * @param string $number
     * @return \Shopsys\ShopBundle\Model\Order\Order
     */
    public function getByNumber(string $number): Order
    {
        $order = $this->findByNumber($number);
        if ($order === null) {
            throw new OrderNotFoundException(sprintf('Order with number `%s` not found', $number));
        }

        return $order;
    }
}

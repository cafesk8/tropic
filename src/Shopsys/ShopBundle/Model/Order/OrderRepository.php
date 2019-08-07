<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use DateTime;
use Doctrine\ORM\Query\Expr\Join;
use GoPay\Definition\Response\PaymentStatus;
use Shopsys\FrameworkBundle\Component\Money\Money;
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
            ->setParameters([
                'customerId' => $customerId,
                'ordersFrom' => $dateFrom,
                'ordersTo' => $dateTo,
            ]);

        return (float)$queryBuilder->getQuery()->getSingleResult()['sum'] ?? 0;
    }

    /**
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @return int[]
     */
    public function getCustomerIdsFromOrdersUpdatedAt(DateTime $startTime, DateTime $endTime): array
    {
        $queryBuilder = $this->createOrderQueryBuilder()
            ->select('IDENTITY(o.customer) as id')
            ->where('o.updatedAt > :startTime')
            ->andWhere('o.updatedAt < :endTime')
            ->andWhere('o.customer is not null')
            ->groupBy('o.customer')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        $customerIds = $queryBuilder->getQuery()->getResult();

        return array_column($customerIds, 'id');
    }

    /**
     * @param int[] $customerIds
     * @return \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public function getOrdersValueIndexedByCustomerId(array $customerIds): array
    {
        $queryBuilder = $this->createOrderQueryBuilder()
            ->select('IDENTITY(o.customer) as customerId')
            ->addSelect('SUM(o.totalPriceWithVat) as ordersValue')
            ->where('o.status = :statusCompleted')
            ->andWhere('o.customer IN (:customerIds)')
            ->groupBy('customerId')
            ->setParameter('statusCompleted', OrderStatus::TYPE_DONE)
            ->setParameter('customerIds', $customerIds);

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
            ->andWhere('o.exportStatus IN (:exportStatus)')
            ->setParameter('exportStatus', [Order::EXPORT_NOT_YET, Order::EXPORT_ERROR])
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

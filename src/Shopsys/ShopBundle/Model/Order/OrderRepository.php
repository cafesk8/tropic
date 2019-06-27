<?php

namespace Shopsys\ShopBundle\Model\Order;

use DateTime;
use Doctrine\ORM\Query\Expr\Join;
use GoPay\Definition\Response\PaymentStatus;
use Shopsys\FrameworkBundle\Model\Order\OrderRepository as BaseOrderRepository;
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
}

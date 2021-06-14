<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use App\Model\Order\Order;
use App\Model\Payment\Payment;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\ResultSetMapping;

class OrderUpdateRepository
{
    private PohodaEntityManager $em;

    /**
     * @param \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager $em
     */
    public function __construct(PohodaEntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param string[] $paymentMethodNames
     * @return int
     */
    public function updatePaymentMethod(Order $order, array $paymentMethodNames): int
    {
        $locale = DomainHelper::DOMAIN_ID_TO_LOCALE[$order->getDomainId()];
        $paymentPriceWithVat = $order->getOrderPayment()->getPriceWithVat();
        $paymentPriceWithoutVat = $order->getOrderPayment()->getPriceWithoutVat();
        $rowsAffected = $this->em->getConnection()->executeUpdate('UPDATE OBJpol SET SText = :newPaymentMethodName, 
                KcJedn = :priceWithVat, Kc = :priceWithoutVat, KcDPH = :vatAmount 
                WHERE RefAg = :orderPohodaId AND SText IN (\'' . implode('\', \'', $paymentMethodNames[$locale]) . '\')', [
            'newPaymentMethodName' => $order->getPaymentName(),
            'priceWithVat' => $paymentPriceWithVat->getAmount(),
            'priceWithoutVat' => $paymentPriceWithoutVat->getAmount(),
            'vatAmount' => $paymentPriceWithVat->subtract($paymentPriceWithoutVat)->getAmount(),
            'orderPohodaId' => $order->getPohodaId(),
        ]);
        $rowsAffected += $this->em->getConnection()->executeUpdate('UPDATE OBJ SET RelForUh = :paymentId WHERE ID = :orderPohodaId', [
            'paymentId' => $this->getPaymentMethodPohodaId($order->getPayment()),
            'orderPohodaId' => $order->getPohodaId(),
        ]);

        return $rowsAffected;
    }

    /**
     * @param \App\Model\Payment\Payment $payment
     * @return int|null
     */
    private function getPaymentMethodPohodaId(Payment $payment): ?int
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('ID', 'paymentId', Types::INTEGER);
        $result = $this->em->createNativeQuery('SELECT ID FROM sFormUh WHERE IDS = :externalId', $rsm)
            ->setParameter('externalId', $payment->getExternalId())
            ->getResult();

        return $result[0]['paymentId'] ?? null;
    }
}

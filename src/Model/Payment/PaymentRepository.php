<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\GoPay\PaymentMethod\GoPayPaymentMethod;
use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Model\Payment\PaymentRepository as BasePaymentRepository;
use Shopsys\FrameworkBundle\Model\Payment\PaymentTranslation;

/**
 * @method \App\Model\Payment\Payment[] getAll()
 * @method \App\Model\Payment\Payment[] getAllIncludingDeleted()
 * @method \App\Model\Payment\Payment|null findById(int $id)
 * @method \App\Model\Payment\Payment getById(int $id)
 * @method \App\Model\Payment\Payment[] getAllByTransport(\App\Model\Transport\Transport $transport)
 * @method \App\Model\Payment\Payment getOneByUuid(string $uuid)
 */
class PaymentRepository extends BasePaymentRepository
{
    /**
     * @param \App\Model\GoPay\PaymentMethod\GoPayPaymentMethod $goPayPaymentMethod
     * @return \App\Model\Payment\Payment[]
     */
    public function getByGoPayPaymentMethod(GoPayPaymentMethod $goPayPaymentMethod): array
    {
        return $this->getPaymentRepository()->findBy(['goPayPaymentMethod' => $goPayPaymentMethod]);
    }

    /**
     * @param string $type
     * @return \App\Model\Payment\Payment[]
     */
    public function getByType(string $type): array
    {
        return $this->getQueryBuilderForAll()
            ->andWhere('p.type = :type')->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $paymentName
     * @param bool $czkRounding
     * @param string $locale
     * @return \App\Model\Payment\Payment|null
     */
    public function findByNameAndCzkRounding(string $paymentName, bool $czkRounding, string $locale): ?Payment
    {
        return $this->getPaymentRepository()->createQueryBuilder('p')
            ->select('p')
            ->join(PaymentTranslation::class, 'pt', Join::WITH, 'pt.translatable = p.id')
            ->where('p.czkRounding = :czkRounding')
            ->andWhere('pt.name = :paymentName')
            ->andWhere('pt.locale = :paymentLocale')
            ->setParameter('czkRounding', $czkRounding)
            ->setParameter('paymentName', $paymentName)
            ->setParameter('paymentLocale', $locale)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $externalId
     * @return \App\Model\Payment\Payment|null
     */
    public function findByExternalId(string $externalId): ?Payment
    {
        return $this->getQueryBuilderForAll()
            ->andWhere('p.externalId = :externalId')
            ->setParameter('externalId', $externalId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

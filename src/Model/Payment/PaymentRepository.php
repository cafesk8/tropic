<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\GoPay\PaymentMethod\GoPayPaymentMethod;
use Shopsys\FrameworkBundle\Model\Payment\PaymentRepository as BasePaymentRepository;

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
}

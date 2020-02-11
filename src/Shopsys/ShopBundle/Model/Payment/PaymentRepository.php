<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Payment;

use Shopsys\FrameworkBundle\Model\Payment\PaymentRepository as BasePaymentRepository;
use Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod;

/**
 * @method \Shopsys\ShopBundle\Model\Payment\Payment[] getAll()
 * @method \Shopsys\ShopBundle\Model\Payment\Payment[] getAllIncludingDeleted()
 * @method \Shopsys\ShopBundle\Model\Payment\Payment|null findById(int $id)
 * @method \Shopsys\ShopBundle\Model\Payment\Payment getById(int $id)
 * @method \Shopsys\ShopBundle\Model\Payment\Payment[] getAllByTransport(\Shopsys\ShopBundle\Model\Transport\Transport $transport)
 */
class PaymentRepository extends BasePaymentRepository
{
    /**
     * @param \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod $goPayPaymentMethod
     * @return \Shopsys\ShopBundle\Model\Payment\Payment[]
     */
    public function getByGoPayPaymentMethod(GoPayPaymentMethod $goPayPaymentMethod): array
    {
        return $this->getPaymentRepository()->findBy(['goPayPaymentMethod' => $goPayPaymentMethod]);
    }

    /**
     * @param string $type
     * @return \Shopsys\ShopBundle\Model\Payment\Payment[]
     */
    public function getByType(string $type): array
    {
        return $this->getQueryBuilderForAll()
            ->andWhere('p.type = :type')->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }
}

<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Payment;

use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade as BasePaymentFacade;
use Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod;

class PaymentFacade extends BasePaymentFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Payment\PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @param \Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod $goPayPaymentMethod
     */
    public function hideByGoPayPaymentMethod(GoPayPaymentMethod $goPayPaymentMethod): void
    {
        $payments = $this->paymentRepository->getByGoPayPaymentMethod($goPayPaymentMethod);

        foreach ($payments as $payment) {
            $payment->hide();
        }

        $this->em->flush($payments);
    }

    /**
     * @param string $type
     * @return \Shopsys\ShopBundle\Model\Payment\Payment|null
     */
    public function getFirstPaymentByType(string $type): ?Payment
    {
        $paymentsByType = $this->paymentRepository->getByType($type);

        if (count($paymentsByType) > 0) {
            return $paymentsByType[0];
        }

        return null;
    }
}

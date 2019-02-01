<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Payment;

use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade as BasePaymentFacade;
use Shopsys\ShopBundle\Model\GoPay\PaymentMethod\GoPayPaymentMethod;

class PaymentFacade extends BasePaymentFacade
{
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
}

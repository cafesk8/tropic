<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Payment;

use Shopsys\FrameworkBundle\Model\Payment\IndependentPaymentVisibilityCalculation as BaseIndependentPaymentVisibilityCalculation;
use Shopsys\FrameworkBundle\Model\Payment\Payment;

class IndependentPaymentVisibilityCalculation extends BaseIndependentPaymentVisibilityCalculation
{
    /**
     * @param \Shopsys\ShopBundle\Model\Payment\Payment $payment
     * @param int $domainId
     * @return bool
     */
    public function isIndependentlyVisible(Payment $payment, $domainId)
    {
        $locale = $this->domain->getDomainConfigById($domainId)->getLocale();

        $paymentName = $payment->getName($locale);
        if ($paymentName === null || $paymentName === '') {
            return false;
        }

        if ($payment->isHidden() || $payment->isHiddenByGoPay()) {
            return false;
        }

        return $payment->isEnabled($domainId);
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Payment;

use Shopsys\FrameworkBundle\Model\Payment\PaymentPriceCalculation as BasePaymentPriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Price;

/**
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price calculatePrice(\App\Model\Payment\Payment $payment, \App\Model\Pricing\Currency\Currency $currency, \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice, int $domainId)
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price calculateIndependentPrice(\App\Model\Payment\Payment $payment, \App\Model\Pricing\Currency\Currency $currency, int $domainId)
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price[] getCalculatedPricesIndexedByPaymentId(\App\Model\Payment\Payment[] $payments, \App\Model\Pricing\Currency\Currency $currency, \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice, int $domainId)
 */
class PaymentPriceCalculation extends BasePaymentPriceCalculation
{
    /**
     * @inheritDoc
     */
    protected function isFree(Price $productsPrice, int $domainId): bool
    {
        return false; // always return false because we don't want free payment
    }
}

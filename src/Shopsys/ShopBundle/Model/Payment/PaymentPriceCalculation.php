<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Payment;

use Shopsys\FrameworkBundle\Model\Payment\PaymentPriceCalculation as BasePaymentPriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Price;

/**
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price calculatePrice(\Shopsys\ShopBundle\Model\Payment\Payment $payment, \Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency $currency, \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice, int $domainId)
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price calculateIndependentPrice(\Shopsys\ShopBundle\Model\Payment\Payment $payment, \Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency $currency)
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price[] getCalculatedPricesIndexedByPaymentId(\Shopsys\ShopBundle\Model\Payment\Payment[] $payments, \Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency $currency, \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice, int $domainId)
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

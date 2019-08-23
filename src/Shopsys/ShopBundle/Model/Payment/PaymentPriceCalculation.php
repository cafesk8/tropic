<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Payment;

use Shopsys\FrameworkBundle\Model\Payment\PaymentPriceCalculation as BasePaymentPriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Price;

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

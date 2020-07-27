<?php

declare(strict_types=1);

namespace App\Model\Payment;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Payment\PaymentDomain as BasePaymentDomain;

/**
 * @ORM\Table(name="payment_domains")
 * @ORM\Entity
 * @property \App\Model\Payment\Payment $payment
 * @property \App\Model\Pricing\Vat\Vat $vat
 * @method \App\Model\Pricing\Vat\Vat getVat()
 * @method setVat(\App\Model\Pricing\Vat\Vat $vat)
 * @method __construct(\App\Model\Payment\Payment $payment, int $domainId, \App\Model\Pricing\Vat\Vat $vat)
 */
class PaymentDomain extends BasePaymentDomain
{
    /**
     * @ORM\Column(type="money", precision=20, scale=6)
     */
    private Money $minimumOrderPrice;

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getMinimumOrderPrice(): Money
    {
        return $this->minimumOrderPrice;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $minimumOrderPrice
     */
    public function setMinimumOrderPrice(Money $minimumOrderPrice): void
    {
        $this->minimumOrderPrice = $minimumOrderPrice;
    }
}

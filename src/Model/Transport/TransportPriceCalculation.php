<?php

declare(strict_types=1);

namespace App\Model\Transport;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation as BaseTransportPriceCalculation;

/**
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price calculateIndependentPrice(\App\Model\Transport\Transport $transport, \App\Model\Pricing\Currency\Currency $currency, int $domainId)
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price[] getCalculatedPricesIndexedByTransportId(\App\Model\Transport\Transport[] $transports, \App\Model\Pricing\Currency\Currency $currency, \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice, int $domainId)
 */
class TransportPriceCalculation extends BaseTransportPriceCalculation
{
    /**
     * @param \App\Model\Transport\Transport $transport
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $productsPrice
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    public function calculatePrice(Transport $transport, Currency $currency, Price $productsPrice, int $domainId): Price
    {
        if ($transport->getPrice($domainId)->isFree($productsPrice)) {
            return $this->basePriceCalculation->calculateBasePriceRoundedByCurrency(
                Money::zero(),
                $this->pricingSetting->getInputPriceType(),
                $transport->getTransportDomain($domainId)->getVat(),
                $currency
            );
        }

        if ($transport->getPrice($domainId)->canUseActionPrice($productsPrice)) {
            return $this->basePriceCalculation->calculateBasePriceRoundedByCurrency(
                $transport->getPrice($domainId)->getActionPrice(),
                $this->pricingSetting->getInputPriceType(),
                $transport->getTransportDomain($domainId)->getVat(),
                $currency
            );
        }

        return $this->calculateIndependentPrice($transport, $currency, $domainId);
    }
}

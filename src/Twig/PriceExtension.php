<?php

declare(strict_types=1);

namespace App\Twig;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Twig\PriceExtension as BasePriceExtension;

class PriceExtension extends BasePriceExtension
{
    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $price
     * @param int $domainId
     * @return string
     */
    public function priceWithCurrencyByDomainIdFilter(Money $price, int $domainId): string
    {
        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId);
        $locale = $this->domain->getDomainConfigById($domainId)->getLocale();

        return $this->formatCurrency($price, $currency, $locale);
    }
}

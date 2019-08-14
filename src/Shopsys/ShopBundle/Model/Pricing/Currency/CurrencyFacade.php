<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Pricing\Currency;

use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade as BaseCurrencyFacade;

class CurrencyFacade extends BaseCurrencyFacade
{
    /**
     * @param string $code
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency|null
     */
    public function findByCode(string $code): ?Currency
    {
        return $this->currencyRepository->findByCode($code);
    }
}

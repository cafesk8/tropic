<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use CommerceGuys\Intl\Formatter\CurrencyFormatter;
use Shopsys\FrameworkBundle\Twig\PriceExtension as BasePriceExtension;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;

class PriceExtension extends BasePriceExtension
{
    private const CZECH_MINIMUM_FRACTION_DIGITS = 0;
    private const CZECH_MAXIMUM_FRACTION_DIGITS = 6;

    /**
     * @param string $locale
     * @return \CommerceGuys\Intl\Formatter\CurrencyFormatter
     */
    protected function getCurrencyFormatter(string $locale): CurrencyFormatter
    {
        if ($locale === DomainHelper::CZECH_LOCALE) {
            $minimumFractionDigits = self::CZECH_MINIMUM_FRACTION_DIGITS;
            $maximumFractionDigits = self::CZECH_MAXIMUM_FRACTION_DIGITS;
        } else {
            $minimumFractionDigits = static::MINIMUM_FRACTION_DIGITS;
            $maximumFractionDigits = static::MAXIMUM_FRACTION_DIGITS;
        }

        return new CurrencyFormatter(
            $this->numberFormatRepository,
            $this->intlCurrencyRepository,
            [
                'locale' => $locale,
                'style' => 'standard',
                'minimum_fraction_digits' => $minimumFractionDigits,
                'maximum_fraction_digits' => $maximumFractionDigits,
            ]
        );
    }
}

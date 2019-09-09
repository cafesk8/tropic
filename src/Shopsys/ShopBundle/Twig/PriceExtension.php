<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use CommerceGuys\Intl\Formatter\NumberFormatter;
use Shopsys\FrameworkBundle\Twig\PriceExtension as BasePriceExtension;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;

class PriceExtension extends BasePriceExtension
{
    private const CZECH_MINIMUM_FRACTION_DIGITS = 0;
    private const CZECH_MAXIMUM_FRACTION_DIGITS = 6;

    /**
     * @param string $locale
     * @return \CommerceGuys\Intl\Formatter\NumberFormatter
     */
    protected function getNumberFormatter(string $locale): NumberFormatter
    {
        $numberFormat = $this->numberFormatRepository->get($locale);
        $numberFormatter = new NumberFormatter($numberFormat, NumberFormatter::CURRENCY);

        if ($locale === DomainHelper::CZECH_LOCALE) {
            $numberFormatter->setMinimumFractionDigits(self::CZECH_MINIMUM_FRACTION_DIGITS);
            $numberFormatter->setMaximumFractionDigits(self::CZECH_MAXIMUM_FRACTION_DIGITS);
        } else {
            $numberFormatter->setMinimumFractionDigits(static::MINIMUM_FRACTION_DIGITS);
            $numberFormatter->setMaximumFractionDigits(static::MAXIMUM_FRACTION_DIGITS);
        }

        return $numberFormatter;
    }
}

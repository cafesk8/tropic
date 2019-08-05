<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use IntlDateFormatter;
use Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension as BaseDateTimeFormatterExtension;
use Twig\TwigFilter;

class DateTimeFormatterExtension extends BaseDateTimeFormatterExtension
{
    /**
     * @return array
     */
    public function getFilters(): array
    {
        return array_merge(parent::getFilters(), [
            new TwigFilter('formatLongDate', [$this, 'formatLongDate']),
        ]);
    }

    /**
     * @param mixed $dateTime
     * @param string|null $locale
     * @return string
     */
    public function formatLongDate($dateTime, $locale = null): string
    {
        return $this->format(
            $dateTime,
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            $locale
        );
    }
}

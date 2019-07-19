<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use DateTime;
use IntlDateFormatter;
use Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension as BaseDateTimeFormatterExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

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
     * @return array
     */
    public function getFunctions(): array
    {
        return array_merge(parent::getFunctions(), [
            new TwigFunction('formatCurrentDateTime', [$this, 'formatCurrentDateTime'], ['is_safe' => ['html']]),
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

    /**
     * @param string $format
     * @return string
     */
    public function formatCurrentDateTime(string $format): string
    {
        return (new DateTime())->format($format);
    }
}

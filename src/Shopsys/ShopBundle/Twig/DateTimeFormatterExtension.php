<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use DateTime;
use IntlDateFormatter;
use Shopsys\FrameworkBundle\Component\Localization\DateTimeFormatterInterface;
use Shopsys\FrameworkBundle\Component\Localization\DisplayTimeZoneProviderInterface;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Twig\DateTimeFormatterExtension as BaseDateTimeFormatterExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class DateTimeFormatterExtension extends BaseDateTimeFormatterExtension
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Localization\DisplayTimeZoneProviderInterface
     */
    private $displayTimeZoneProvider;

    /**
     * @var \Twig_Environment
     */
    private $twigEnvironment;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Localization\DateTimeFormatterInterface $dateTimeFormatter
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     * @param \Shopsys\FrameworkBundle\Component\Localization\DisplayTimeZoneProviderInterface $displayTimeZoneProvider
     * @param \Twig_Environment $twigEnvironment
     */
    public function __construct(
        DateTimeFormatterInterface $dateTimeFormatter,
        Localization $localization,
        DisplayTimeZoneProviderInterface $displayTimeZoneProvider,
        \Twig_Environment $twigEnvironment
    ) {
        parent::__construct($dateTimeFormatter, $localization);
        $this->displayTimeZoneProvider = $displayTimeZoneProvider;
        $this->twigEnvironment = $twigEnvironment;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return array_merge(parent::getFilters(), [
            new TwigFilter('formatLongDate', [$this, 'formatLongDate']),
            new TwigFilter('formatDateCore', [$this, 'formatDateCore']),
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

    /**
     * @param \DateTime|string $dateTime
     * @param string $format
     * @return string
     */
    public function formatDateCore($dateTime, string $format): string
    {
        $displayTimeZone = $this->displayTimeZoneProvider->getDisplayTimeZone();

        return twig_date_format_filter($this->twigEnvironment, $this->convertToDateTime($dateTime), $format, $displayTimeZone);
    }
}

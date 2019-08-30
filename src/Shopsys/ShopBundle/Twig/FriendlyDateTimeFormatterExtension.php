<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use DateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FriendlyDateTimeFormatterExtension extends AbstractExtension
{
    /**
     * @return \Twig\TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('friendlyDay', [$this, 'friendlyDay']),
        ];
    }

    /**
     * @param \DateTime $day
     * @return string
     */
    public function friendlyDay(DateTime $day): string
    {
        $tomorrow = new DateTime('tomorrow');

        if ($tomorrow->format('Y-m-d') === $day->format('Y-m-d')) {
            return t('Zítra');
        }

        $today = new DateTime();
        if ($today->format('Y-m-d') === $day->format('Y-m-d')) {
            return t('Dnes');
        }

        $daysInWeekTranslations = [
            1 => t('V pondělí'),
            2 => t('V úterý'),
            3 => t('Ve středu'),
            4 => t('Ve čtvrtek'),
            5 => t('V pátek'),
            6 => t('V sobotu'),
            7 => t('V neděli'),
        ];

        return $daysInWeekTranslations[$day->format('N')];
    }
}

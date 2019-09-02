<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Domain;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Heureka\Exception\LocaleNotSupportedException;

class DomainHelper
{
    public const CZECH_DOMAIN = 1;
    public const SLOVAK_DOMAIN = 2;
    public const GERMAN_DOMAIN = 3;

    public const CZECH_LOCALE = 'cs';
    public const SLOVAK_LOCALE = 'sk';
    public const GERMAN_LOCALE = 'de';

    public const LOCALES = [self::CZECH_LOCALE, self::SLOVAK_LOCALE, self::GERMAN_LOCALE];

    public const CZECH_COUNTRY_CODE = 'CZ';
    public const SLOVAK_COUNTRY_CODE = 'SK';
    public const GERMAN_COUNTRY_CODE = 'DE';

    public const DOMAIN_ID_BY_COUNTRY_CODE = [
        self::CZECH_COUNTRY_CODE => self::CZECH_DOMAIN,
        self::SLOVAK_COUNTRY_CODE => self::SLOVAK_DOMAIN,
        self::GERMAN_COUNTRY_CODE => self::GERMAN_DOMAIN,
    ];

    public const COUNTRY_CODE_BY_LOCALE = [
        self::CZECH_LOCALE => self::CZECH_COUNTRY_CODE,
        self::SLOVAK_LOCALE => self::SLOVAK_COUNTRY_CODE,
        self::GERMAN_LOCALE => self::GERMAN_COUNTRY_CODE,
    ];

    public const DOMAIN_ID_TO_TRANSFER_SOURCE = [
        self::CZECH_DOMAIN => 'LPKCZ',
        self::SLOVAK_DOMAIN => 'BSHTR',
        self::GERMAN_DOMAIN => 'BSHDE',
    ];

    /**
     * @param string $locale
     * @return string
     */
    public static function getCountryCodeByLocale(string $locale): string
    {
        if (isset(self::COUNTRY_CODE_BY_LOCALE[$locale]) === false) {
            throw new LocaleNotSupportedException();
        }

        return self::COUNTRY_CODE_BY_LOCALE[$locale];
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @return bool
     */
    public static function isGermanDomain(Domain $domain): bool
    {
        return $domain->getLocale() === self::GERMAN_LOCALE;
    }
}

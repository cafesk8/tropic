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

    public const SOURCE_LPKCZ = 'LPKCZ';
    public const SOURCE_BSHTR = 'BSHTR';
    public const SOURCE_BSHDE = 'BSHDE';

    public const DOMAIN_ID_BY_COUNTRY_CODE = [
        self::CZECH_COUNTRY_CODE => self::CZECH_DOMAIN,
        self::SLOVAK_COUNTRY_CODE => self::SLOVAK_DOMAIN,
        self::GERMAN_COUNTRY_CODE => self::GERMAN_DOMAIN,
    ];

    public const COUNTRY_CODE_BY_DOMAIN_ID = [
        self::CZECH_DOMAIN => self::CZECH_COUNTRY_CODE,
        self::SLOVAK_DOMAIN => self::SLOVAK_COUNTRY_CODE,
        self::GERMAN_DOMAIN => self::GERMAN_COUNTRY_CODE,
    ];

    public const COUNTRY_CODE_BY_LOCALE = [
        self::CZECH_LOCALE => self::CZECH_COUNTRY_CODE,
        self::SLOVAK_LOCALE => self::SLOVAK_COUNTRY_CODE,
        self::GERMAN_LOCALE => self::GERMAN_COUNTRY_CODE,
    ];

    public const DOMAIN_ID_TO_TRANSFER_SOURCE = [
        self::CZECH_DOMAIN => self::SOURCE_LPKCZ,
        self::SLOVAK_DOMAIN => self::SOURCE_BSHTR,
        self::GERMAN_DOMAIN => self::SOURCE_BSHDE,
    ];

    public const TRANSFER_SOURCE_TO_DOMAIN_ID = [
        self::SOURCE_LPKCZ => self::CZECH_DOMAIN,
        self::SOURCE_BSHTR => self::SLOVAK_DOMAIN,
        self::SOURCE_BSHDE => self::GERMAN_DOMAIN,
    ];

    public const DOMAIN_ID_TO_LOCALE = [
        self::CZECH_DOMAIN => self::CZECH_LOCALE,
        self::SLOVAK_DOMAIN => self::SLOVAK_LOCALE,
        self::GERMAN_DOMAIN => self::GERMAN_LOCALE,
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

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @return bool
     */
    public static function isSlovakDomain(Domain $domain): bool
    {
        return $domain->getLocale() === self::SLOVAK_LOCALE;
    }
}

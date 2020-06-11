<?php

declare(strict_types=1);

namespace App\Component\Domain;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Heureka\Exception\LocaleNotSupportedException;

class DomainHelper
{
    public const CZECH_DOMAIN = 1;
    public const SLOVAK_DOMAIN = 2;
    public const ENGLISH_DOMAIN = 3;

    public const CZECH_LOCALE = 'cs';
    public const SLOVAK_LOCALE = 'sk';
    public const ENGLISH_LOCALE = 'en';

    public const LOCALES = [self::CZECH_LOCALE, self::SLOVAK_LOCALE, self::ENGLISH_LOCALE];

    public const CZECH_COUNTRY_CODE = 'CZ';
    public const SLOVAK_COUNTRY_CODE = 'SK';
    public const ENGLISH_COUNTRY_CODE = 'GB';

    public const SOURCE_LPKCZ = 'LPKCZ';
    public const SOURCE_BSHTR = 'BSHTR';
    public const SOURCE_BSHDE = 'BSHDE';

    public const DOMAIN_ID_BY_COUNTRY_CODE = [
        self::CZECH_COUNTRY_CODE => self::CZECH_DOMAIN,
        self::SLOVAK_COUNTRY_CODE => self::SLOVAK_DOMAIN,
        self::ENGLISH_COUNTRY_CODE => self::ENGLISH_DOMAIN,
    ];

    public const COUNTRY_CODE_BY_DOMAIN_ID = [
        self::CZECH_DOMAIN => self::CZECH_COUNTRY_CODE,
        self::SLOVAK_DOMAIN => self::SLOVAK_COUNTRY_CODE,
        self::ENGLISH_DOMAIN => self::ENGLISH_COUNTRY_CODE,
    ];

    public const COUNTRY_CODE_BY_LOCALE = [
        self::CZECH_LOCALE => self::CZECH_COUNTRY_CODE,
        self::SLOVAK_LOCALE => self::SLOVAK_COUNTRY_CODE,
        self::ENGLISH_LOCALE => self::ENGLISH_COUNTRY_CODE,
    ];

    public const DOMAIN_ID_TO_TRANSFER_SOURCE = [
        self::CZECH_DOMAIN => self::SOURCE_LPKCZ,
        self::SLOVAK_DOMAIN => self::SOURCE_BSHTR,
        self::ENGLISH_DOMAIN => self::SOURCE_BSHDE,
    ];

    public const TRANSFER_SOURCE_TO_DOMAIN_ID = [
        self::SOURCE_LPKCZ => self::CZECH_DOMAIN,
        self::SOURCE_BSHTR => self::SLOVAK_DOMAIN,
        self::SOURCE_BSHDE => self::ENGLISH_DOMAIN,
    ];

    public const DOMAIN_ID_TO_LOCALE = [
        self::CZECH_DOMAIN => self::CZECH_LOCALE,
        self::SLOVAK_DOMAIN => self::SLOVAK_LOCALE,
        self::ENGLISH_DOMAIN => self::ENGLISH_LOCALE,
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
    public static function isEnglishDomain(Domain $domain): bool
    {
        return $domain->getLocale() === self::ENGLISH_LOCALE;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @return bool
     */
    public static function isSlovakDomain(Domain $domain): bool
    {
        return $domain->getLocale() === self::SLOVAK_LOCALE;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @return bool
     */
    public static function isCzechDomain(Domain $domain): bool
    {
        return $domain->getLocale() === self::CZECH_LOCALE;
    }
}

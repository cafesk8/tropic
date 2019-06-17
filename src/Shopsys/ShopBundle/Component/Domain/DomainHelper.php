<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Domain;

class DomainHelper
{
    public const CZECH_DOMAIN = 1;
    public const SLOVAK_DOMAIN = 2;
    public const GERMAN_DOMAIN = 3;

    public const CZECH_LOCALE = 'cs';
    public const SLOVAK_LOCALE = 'sk';
    public const GERMAN_LOCALE = 'de';

    public const DOMAIN_ID_BY_COUNTRY_CODE = [
        'CZ' => self::CZECH_DOMAIN,
        'SK' => self::SLOVAK_DOMAIN,
        'DE' => self::GERMAN_DOMAIN,
    ];
}

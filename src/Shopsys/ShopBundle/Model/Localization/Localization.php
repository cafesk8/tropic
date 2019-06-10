<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Localization;

use Shopsys\FrameworkBundle\Model\Localization\Localization as BaseLocalization;

class Localization extends BaseLocalization
{
    /**
     * @var string[]
     */
    protected $collationsByLocale = [
        'cs' => 'cs-CZ-x-icu',
        'de' => 'de-DE-x-icu',
        'en' => 'en-US-x-icu',
        'hu' => 'hu-HU-x-icu',
        'pl' => 'pl-PL-x-icu',
        'sk' => 'sk-SK-x-icu',
    ];
}

<?php

declare(strict_types=1);

namespace App\Model\Country;

use Shopsys\FrameworkBundle\Model\Country\CountryData as BaseCountryData;

class CountryData extends BaseCountryData
{
    /**
     * @var string|null
     */
    public $externalId;
}

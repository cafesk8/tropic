<?php

declare(strict_types=1);

namespace App\Model\Country;

use Shopsys\FrameworkBundle\Model\Country\Country as BaseCountry;
use Shopsys\FrameworkBundle\Model\Country\CountryData as BaseCountryData;
use Shopsys\FrameworkBundle\Model\Country\CountryDataFactory as BaseCountryDataFactory;

/**
 * @method fillNew(\App\Model\Country\CountryData $countryData)
 */
class CountryDataFactory extends BaseCountryDataFactory
{
    /**
     * @return \App\Model\Country\CountryData
     */
    public function create(): BaseCountryData
    {
        $countryData = new CountryData();
        $this->fillNew($countryData);

        return $countryData;
    }

    /**
     * @param \App\Model\Country\Country $country
     * @return \App\Model\Country\CountryData
     */
    public function createFromCountry(BaseCountry $country): BaseCountryData
    {
        $countryData = new CountryData();
        $this->fillFromCountry($countryData, $country);

        return $countryData;
    }

    /**
     * @param \App\Model\Country\CountryData $countryData
     * @param \App\Model\Country\Country $country
     */
    protected function fillFromCountry(BaseCountryData $countryData, BaseCountry $country): void
    {
        parent::fillFromCountry($countryData, $country);

        $countryData->externalId = $country->getExternalId();
    }
}

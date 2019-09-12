<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Country;

use Shopsys\FrameworkBundle\Model\Country\Country as BaseCountry;
use Shopsys\FrameworkBundle\Model\Country\CountryData as BaseCountryData;
use Shopsys\FrameworkBundle\Model\Country\CountryDataFactory as BaseCountryDataFactory;

class CountryDataFactory extends BaseCountryDataFactory
{
    /**
     * @return \Shopsys\ShopBundle\Model\Country\CountryData
     */
    public function create(): BaseCountryData
    {
        $countryData = new CountryData();
        $this->fillNew($countryData);

        return $countryData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Country\Country $country
     * @return \Shopsys\ShopBundle\Model\Country\CountryData
     */
    public function createFromCountry(BaseCountry $country): BaseCountryData
    {
        $countryData = new CountryData();
        $this->fillFromCountry($countryData, $country);

        return $countryData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Country\CountryData $countryData
     * @param \Shopsys\ShopBundle\Model\Country\Country $country
     */
    protected function fillFromCountry(BaseCountryData $countryData, BaseCountry $country): void
    {
        parent::fillFromCountry($countryData, $country);

        $countryData->externalId = $country->getExternalId();
    }
}

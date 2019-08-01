<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Country;

use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Country\CountryFacade as BaseCountryFacade;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;

class CountryFacade extends BaseCountryFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Country\CountryRepository
     */
    protected $countryRepository;

    /**
     * @param string $code
     * @return \Shopsys\FrameworkBundle\Model\Country\Country
     */
    public function getByCode(string $code): Country
    {
        return $this->countryRepository->getByCode($code);
    }

    /**
     * @return string[]
     */
    public function getAllCodesInArray(): array
    {
        return $this->countryRepository->getAllCodesInArray();
    }

    /**
     * @return string[]
     */
    public function getAllCodesForDomainInArray(): array
    {
        $countries = $this->countryRepository->getAllEnabledByDomainIdWithLocale($this->domain->getId(), $this->domain->getLocale());

        return array_map(static function (Country $country) {
            return $country->getCode();
        }, $countries);
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Country\Country
     */
    public function getHackedCountry(): Country
    {
        if (DomainHelper::isGermanDomain($this->domain) === false) {
            $countryCode = DomainHelper::getCountryCodeByLocale($this->domain->getLocale());
            return $this->getByCode($countryCode);
        }

        $countryId = $_SESSION['_sf2_attributes']['craue_form_flow']['order']['flow_order']['data'][2]['country'] ?? null;

        if ($countryId === null) {
            return $this->findByCode(DomainHelper::GERMAN_COUNTRY_CODE);
        }

        return $this->getById($countryId);
    }
}

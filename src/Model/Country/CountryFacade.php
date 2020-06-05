<?php

declare(strict_types=1);

namespace App\Model\Country;

use App\Component\Domain\DomainHelper;
use Shopsys\FrameworkBundle\Model\Country\Country as BaseCountry;
use Shopsys\FrameworkBundle\Model\Country\CountryFacade as BaseCountryFacade;

/**
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Country\CountryRepository $countryRepository, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \Shopsys\FrameworkBundle\Model\Country\CountryFactoryInterface $countryFactory)
 * @method \App\Model\Country\Country getById(int $countryId)
 * @method \App\Model\Country\Country create(\App\Model\Country\CountryData $countryData)
 * @method \App\Model\Country\Country edit(int $countryId, \App\Model\Country\CountryData $countryData)
 * @method \App\Model\Country\Country[] getAll()
 * @method \App\Model\Country\Country[] getAllEnabledOnDomain(int $domainId)
 * @method \App\Model\Country\Country[] getAllOnDomain(int $domainId)
 * @method \App\Model\Country\Country[] getAllEnabledOnCurrentDomain()
 * @method \App\Model\Country\Country|null findByCode(string $countryCode)
 */
class CountryFacade extends BaseCountryFacade
{
    /**
     * @var \App\Model\Country\CountryRepository
     */
    protected $countryRepository;

    /**
     * @param string $code
     * @return \App\Model\Country\Country
     */
    public function getByCode(string $code): BaseCountry
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

        return array_map(static function (BaseCountry $country) {
            return $country->getCode();
        }, $countries);
    }

    /**
     * @return \App\Model\Country\Country
     */
    public function getHackedCountry(): BaseCountry
    {
        if (DomainHelper::isEnglishDomain($this->domain) === false) {
            return $this->getDefaultCountryByDomainId($this->domain->getId());
        }

        $countryId = $_SESSION['_sf2_attributes']['craue_form_flow']['order']['flow_order']['data'][2]['country'] ?? null;

        if ($countryId === null) {
            return $this->findByCode(DomainHelper::ENGLISH_COUNTRY_CODE);
        }

        return $this->getById($countryId);
    }

    /**
     * @param int $domainId
     * @return \App\Model\Country\Country
     */
    public function getDefaultCountryByDomainId(int $domainId): Country
    {
        $countryCode = DomainHelper::getCountryCodeByLocale($this->domain->getDomainConfigById($domainId)->getLocale());

        return $this->getByCode($countryCode);
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Country;

use Doctrine\ORM\AbstractQuery;
use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Country\CountryRepository as BaseCountryRepository;
use Shopsys\FrameworkBundle\Model\Country\Exception\CountryNotFoundException;

/**
 * @method \App\Model\Country\Country|null findById(int $countryId)
 * @method \App\Model\Country\Country getById(int $countryId)
 * @method \App\Model\Country\Country[] getAll()
 * @method \App\Model\Country\Country[] getAllEnabledByDomainIdWithLocale(int $domainId, string $locale)
 * @method \App\Model\Country\Country[] getAllByDomainIdWithLocale(int $domainId, string $locale)
 * @method \App\Model\Country\Country|null findByCode(string $countryCode)
 */
class CountryRepository extends BaseCountryRepository
{
    /**
     * @param string $code
     * @return \App\Model\Country\Country
     */
    public function getByCode(string $code): Country
    {
        /** @var \App\Model\Country\Country|null $country */
        $country = $this->getCountryRepository()->findOneBy(['code' => strtoupper($code)]);
        if ($country === null) {
            $message = sprintf('Country with code ISO `%s` was not found.', $code);
            throw new CountryNotFoundException($message);
        }

        return $country;
    }

    /**
     * @return string[]
     */
    public function getAllCodesInArray(): array
    {
        $results = $this->getCountryRepository()->createQueryBuilder('c')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);

        return array_column($results, 'code');
    }
}

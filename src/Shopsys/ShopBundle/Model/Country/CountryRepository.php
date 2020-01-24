<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Country;

use Doctrine\ORM\AbstractQuery;
use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Country\CountryRepository as BaseCountryRepository;
use Shopsys\FrameworkBundle\Model\Country\Exception\CountryNotFoundException;

/**
 * @method \Shopsys\ShopBundle\Model\Country\Country|null findById(int $countryId)
 * @method \Shopsys\ShopBundle\Model\Country\Country getById(int $countryId)
 * @method \Shopsys\ShopBundle\Model\Country\Country[] getAll()
 * @method \Shopsys\ShopBundle\Model\Country\Country[] getAllEnabledByDomainIdWithLocale(int $domainId, string $locale)
 * @method \Shopsys\ShopBundle\Model\Country\Country[] getAllByDomainIdWithLocale(int $domainId, string $locale)
 * @method \Shopsys\ShopBundle\Model\Country\Country|null findByCode(string $countryCode)
 */
class CountryRepository extends BaseCountryRepository
{
    /**
     * @param string $code
     * @return \Shopsys\ShopBundle\Model\Country\Country|object
     */
    public function getByCode(string $code): Country
    {
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

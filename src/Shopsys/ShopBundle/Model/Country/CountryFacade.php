<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Country;

use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Country\CountryFacade as BaseCountryFacade;

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
}

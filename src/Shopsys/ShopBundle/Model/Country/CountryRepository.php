<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Country;

use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Country\CountryRepository as BaseCountryRepository;
use Shopsys\FrameworkBundle\Model\Country\Exception\CountryNotFoundException;

class CountryRepository extends BaseCountryRepository
{
    /**
     * @param string $code
     * @return \Shopsys\FrameworkBundle\Model\Country\Country|object
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
}

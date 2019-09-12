<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Country;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Country\Country as BaseCountry;
use Shopsys\FrameworkBundle\Model\Country\CountryData;
use Shopsys\FrameworkBundle\Model\Country\CountryData as BaseCountryData;

/**
 * @ORM\Table(name="countries")
 * @ORM\Entity
 *
 * @method CountryTranslation translation(?string $locale = null)
 */
class Country extends BaseCountry
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $externalId;

    /**
     * @param \Shopsys\ShopBundle\Model\Country\CountryData $countryData
     */
    public function __construct(BaseCountryData $countryData)
    {
        parent::__construct($countryData);

        $this->externalId = $countryData->externalId;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Country\CountryData $countryData
     */
    public function edit(CountryData $countryData): void
    {
        parent::edit($countryData);

        $this->externalId = $countryData->externalId;
    }

    /**
     * @return string|null
     */
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }
}

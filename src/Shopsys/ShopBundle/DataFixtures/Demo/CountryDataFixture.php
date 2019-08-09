<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Model\Country\CountryData;
use Shopsys\FrameworkBundle\Model\Country\CountryDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Country\CountryFacade;

class CountryDataFixture extends AbstractReferenceFixture
{
    public const COUNTRY_CZECH_REPUBLIC = 'country_czech_republic';
    public const COUNTRY_SLOVAKIA = 'country_slovakia';
    public const COUNTRY_GERMANY = 'country_germany';
    public const COUNTRY_FRANCE = 'country_france';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Country\CountryFacade
     */
    protected $countryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Country\CountryDataFactoryInterface
     */
    protected $countryDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Country\CountryFacade $countryFacade
     * @param \Shopsys\FrameworkBundle\Model\Country\CountryDataFactoryInterface $countryDataFactory
     */
    public function __construct(CountryFacade $countryFacade, CountryDataFactoryInterface $countryDataFactory)
    {
        $this->countryFacade = $countryFacade;
        $this->countryDataFactory = $countryDataFactory;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $countryData = $this->countryDataFactory->create();
        $countryData->names = [
            'cs' => 'Česká republika',
            'sk' => 'Česká republika',
            'de' => 'Tschechische Republik',
        ];
        $countryData->code = 'CZ';
        $this->createCountry($countryData, self::COUNTRY_CZECH_REPUBLIC);

        $countryData = $this->countryDataFactory->create();
        $countryData->names = [
            'cs' => 'Slovenská republika',
            'sk' => 'Slovenská republika',
            'de' => 'Slowakische Republik',
        ];
        $countryData->code = 'SK';

        $this->createCountry($countryData, self::COUNTRY_SLOVAKIA);

        $countryData = $this->countryDataFactory->create();
        $countryData->names = [
            'cs' => 'Německo',
            'sk' => 'Nemecko',
            'de' => 'Deutschland',
        ];
        $countryData->code = 'DE';
        $this->createCountry($countryData, self::COUNTRY_GERMANY);

        $countryData = $this->countryDataFactory->create();
        $countryData->names = [
            'cs' => 'Francie',
            'sk' => 'Francúzsko',
            'de' => 'Frankreich',
        ];
        $countryData->code = 'FR';
        $this->createCountry($countryData, self::COUNTRY_FRANCE);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Country\CountryData $countryData
     * @param string $referenceName
     */
    protected function createCountry(CountryData $countryData, $referenceName): void
    {
        $country = $this->countryFacade->create($countryData);
        $this->addReference($referenceName, $country);
    }
}

<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Model\Store\StoreData;
use Shopsys\ShopBundle\Model\Store\StoreDataFactory;
use Shopsys\ShopBundle\Model\Store\StoreFacade;

class StoreDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    public const REFERENCE_STORE_BRNO_FUTURUM = 'store_brno_futurum';
    public const REFERENCE_STORE_OSTRAVA_AVION = 'store_ostrava_avion';
    public const REFERENCE_STORE_OSTRAVA_KAROLINA = 'store_ostrava_karolina';
    public const REFERENCE_STORE_BRATISLAVA_AUPARK = 'store_bratislava_aupark';
    public const REFERENCE_STORE_NEMARKT = 'store_neumarkt';

    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreDataFactory
     */
    private $storeDataFactory;

    /**
     * @param \Shopsys\ShopBundle\Model\Store\StoreFacade $storeFacade
     * @param \Shopsys\ShopBundle\Model\Store\StoreDataFactory $storeDataFactory
     */
    public function __construct(StoreFacade $storeFacade, StoreDataFactory $storeDataFactory)
    {
        $this->storeFacade = $storeFacade;
        $this->storeDataFactory = $storeDataFactory;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $description = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
            tempor incididunt ut labore et dolore magna aliqua. <strong>Ut enim ad minim veniam,
            quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
            consequat.</strong> Duis aute irure dolor in reprehenderit in voluptate velit esse
            cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
            proident, sunt in culpa qui officia deserunt mollit anim id est laborum.<br /><br />
            Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
            tempor incididunt ut labore et dolore magna aliqua. <strong>Ut enim ad minim veniam,
            quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
            consequat.</strong> Duis aute irure dolor in reprehenderit in voluptate velit esse
            cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
            proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

        $storeData = $this->storeDataFactory->create();

        $storeData->description = $description;
        $storeData->domainId = DomainHelper::CZECH_DOMAIN;
        $storeData->name = 'Brno Futurum';
        $storeData->city = 'Brno';
        $storeData->street = 'Vídeňská 100';
        $storeData->postcode = '639 00';
        $storeData->openingHours = 'Po-Ne / 10,00 - 20,00';
        $storeData->externalNumber = '0039';
        $storeData->pickupPlace = true;
        $storeData->franchisor = true;
        $storeData->centralStore = true;
        $storeData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $this->createStore($storeData, self::REFERENCE_STORE_BRNO_FUTURUM);

        $storeData->description = $description;
        $storeData->domainId = DomainHelper::CZECH_DOMAIN;
        $storeData->name = 'Ostrava Avion';
        $storeData->city = 'Ostrava';
        $storeData->street = 'Rudná 114';
        $storeData->postcode = '700 30';
        $storeData->openingHours = 'Po-Ne / 9,00 - 21,00';
        $storeData->externalNumber = '0040';
        $storeData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $storeData->pickupPlace = true;
        $storeData->franchisor = false;
        $storeData->centralStore = false;
        $this->createStore($storeData, self::REFERENCE_STORE_OSTRAVA_AVION);

        $storeData->description = $description;
        $storeData->domainId = DomainHelper::CZECH_DOMAIN;
        $storeData->name = 'Ostrava Nová Karolína';
        $storeData->city = 'Ostrava';
        $storeData->street = 'Rudná 114';
        $storeData->postcode = '700 30';
        $storeData->openingHours = 'Po-Ne / 9,00 - 21,00';
        $storeData->externalNumber = '0060';
        $storeData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $this->createStore($storeData, self::REFERENCE_STORE_OSTRAVA_KAROLINA);

        $storeData->description = $description;
        $storeData->domainId = DomainHelper::SLOVAK_DOMAIN;
        $storeData->name = 'SC AUPARK';
        $storeData->city = 'Bratislava';
        $storeData->street = 'Einsteinova 3541/18';
        $storeData->postcode = '85101';
        $storeData->openingHours = 'Po-Pia / 10,00 - 21,00 So-Ne / 9,00 - 21,00';
        $storeData->externalNumber = '1001';
        $storeData->country = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $this->createStore($storeData, self::REFERENCE_STORE_BRATISLAVA_AUPARK);

        $storeData->description = $description;
        $storeData->domainId = DomainHelper::GERMAN_DOMAIN;
        $storeData->name = 'Bushman Germany GmbH';
        $storeData->city = 'Neumarkt';
        $storeData->street = 'Sachsenstraße 2';
        $storeData->postcode = '92318';
        $storeData->openingHours = 'Mo-So / 10,00 - 21,00';
        $storeData->externalNumber = '1003';
        $storeData->country = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $this->createStore($storeData, self::REFERENCE_STORE_NEMARKT);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Store\StoreData $storeData
     * @param string|null $referenceName
     */
    private function createStore(StoreData $storeData, ?string $referenceName): void
    {
        $store = $this->storeFacade->create($storeData);

        if ($referenceName !== null) {
            $this->addReference($referenceName, $store);
        }
    }

    public function getDependencies()
    {
        return [
            CountryDataFixture::class,
        ];
    }
}

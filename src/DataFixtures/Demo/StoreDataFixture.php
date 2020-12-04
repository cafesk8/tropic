<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Store\StoreData;
use App\Model\Store\StoreDataFactory;
use App\Model\Store\StoreFacade;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;

class StoreDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    public const REFERENCE_STORE_SALE_STOCK = 'stock_sale';
    public const REFERENCE_STORE_SALE_STORE = 'store_sale';
    public const REFERENCE_STORE_INTERNAL_STOCK = 'stock_internal';
    public const REFERENCE_STORE_EXTERNAL_STOCK = 'stock_external';
    public const REFERENCE_STORE_STORE_STOCK = 'stock_store';

    /**
     * @var \App\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @var \App\Model\Store\StoreDataFactory
     */
    private $storeDataFactory;

    /**
     * @param \App\Model\Store\StoreFacade $storeFacade
     * @param \App\Model\Store\StoreDataFactory $storeDataFactory
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
        $storeData = $this->storeDataFactory->create();

        $storeData->description = t('Výprodejový sklad', [], 'dataFixtures');
        $storeData->name = t('Výprodej', [], 'dataFixtures');
        $storeData->position = 0;
        $storeData->postcode = '';
        $storeData->centralStore = true;
        $storeData->showOnStoreList = false;
        $storeData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $storeData->externalNumber = '11';
        $storeData->pohodaName = 'VÝPRODEJ';
        $this->createStore($storeData, self::REFERENCE_STORE_SALE_STOCK);

        $storeData->description = t('Výprodejový sklad na prodejně', [], 'dataFixtures');
        $storeData->name = t('Prodejna - výprodej', [], 'dataFixtures');
        $storeData->position = 1;
        $storeData->centralStore = false;
        $storeData->externalNumber = '8';
        $storeData->pohodaName = 'PRODEJNA-V';
        $this->createStore($storeData, self::REFERENCE_STORE_SALE_STORE);

        $storeData->description = '';
        $storeData->name = t('Interní sklad', [], 'dataFixtures');
        $storeData->position = 2;
        $storeData->centralStore = true;
        $storeData->externalNumber = '2';
        $storeData->pohodaName = 'TROPIC';
        $this->createStore($storeData, self::REFERENCE_STORE_INTERNAL_STOCK);

        $storeData->description = '';
        $storeData->name = t('Externí sklad', [], 'dataFixtures');
        $storeData->position = 3;
        $storeData->centralStore = false;
        $storeData->externalNumber = '99';
        $storeData->pohodaName = 'EXTERNÍ';
        $this->createStore($storeData, self::REFERENCE_STORE_EXTERNAL_STOCK);

        $storeData->description = t('Skladové zásoby na prodejně', [], 'dataFixtures');
        $storeData->name = t('Kamenná prodejna v Liberci', [], 'dataFixtures');
        $storeData->position = 4;
        $storeData->city = 'Horní Růžodol, Liberec';
        $storeData->region = 'Liberecký';
        $storeData->street = 'Dr. Milady Horákové 11';
        $storeData->postcode = '460 07';
        $storeData->openingHours = t('Po - Pá: 9:00 - 18:00, So: 9:00 - 12:00', [], 'dataFixtures');
        $storeData->googleMapsLink = 'https://www.google.com/maps/place/Tropic+Liberec+Ltd./@50.754004,15.054671,15z/data=!4m5!3m4!1s0x0:0x1bdf572ee2cd0366!8m2!3d50.754004!4d15.054671';
        $storeData->email = 'prodejna@tropicliberec.cz';
        $storeData->telephone = '+420 777 862 119';
        $storeData->pickupPlace = true;
        $storeData->centralStore = false;
        $storeData->showOnStoreList = true;
        $storeData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $storeData->externalNumber = '4';
        $storeData->pohodaName = 'PRODEJNA';
        $this->createStore($storeData, self::REFERENCE_STORE_STORE_STOCK);
    }

    /**
     * @param \App\Model\Store\StoreData $storeData
     * @param string|null $referenceName
     */
    private function createStore(StoreData $storeData, ?string $referenceName): void
    {
        $store = $this->storeFacade->create($storeData);

        if ($referenceName !== null) {
            $this->addReference($referenceName, $store);
        }
    }

    /**
     * @return array<int,string>
     */
    public function getDependencies(): array
    {
        return [
            CountryDataFixture::class,
        ];
    }
}

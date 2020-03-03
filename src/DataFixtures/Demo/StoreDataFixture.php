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
        $storeData->pickupPlace = false;
        $storeData->franchisor = false;
        $storeData->centralStore = true;
        $storeData->showOnStoreList = false;
        $storeData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $this->createStore($storeData, self::REFERENCE_STORE_SALE_STOCK);

        $storeData->description = t('Výprodejový sklad na prodejně', [], 'dataFixtures');
        $storeData->name = t('Prodejna - výprodej', [], 'dataFixtures');
        $storeData->position = 1;
        $storeData->centralStore = false;
        $this->createStore($storeData, self::REFERENCE_STORE_SALE_STORE);

        $storeData->description = '';
        $storeData->name = t('Interní sklad', [], 'dataFixtures');
        $storeData->position = 2;
        $storeData->centralStore = true;
        $this->createStore($storeData, self::REFERENCE_STORE_INTERNAL_STOCK);

        $storeData->description = '';
        $storeData->name = t('Externí sklad', [], 'dataFixtures');
        $storeData->position = 3;
        $storeData->centralStore = false;
        $this->createStore($storeData, self::REFERENCE_STORE_EXTERNAL_STOCK);

        $storeData->description = t('Skladové zásoby na prodejně', [], 'dataFixtures');
        $storeData->name = t('Prodejna', [], 'dataFixtures');
        $storeData->position = 4;
        $storeData->city = 'Liberec';
        $storeData->street = 'Dr. Milady Horákové 76';
        $storeData->postcode = '460 07';
        $storeData->openingHours = t('Po - Pá: 9:00 - 18:00, So: 9:00 - 12:00', [], 'dataFixtures');
        $storeData->pickupPlace = true;
        $storeData->centralStore = false;
        $storeData->showOnStoreList = true;
        $storeData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
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

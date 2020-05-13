<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Component\Setting\Setting;
use App\Model\Product\Availability\AvailabilityData;
use App\Model\Product\Availability\AvailabilityDataFactory;
use App\Model\Product\Availability\AvailabilityFacade;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;

class AvailabilityDataFixture extends AbstractReferenceFixture
{
    public const AVAILABILITY_IN_STOCK = 'availability_in_stock';
    public const AVAILABILITY_ON_REQUEST = 'availability_on_request';
    public const AVAILABILITY_OUT_OF_STOCK = 'availability_out_of_stock';
    public const AVAILABILITY_PREPARING = 'availability_preparing';

    /**
     * @var \App\Model\Product\Availability\AvailabilityFacade
     */
    protected $availabilityFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityDataFactoryInterface
     */
    protected $availabilityDataFactory;

    /**
     * @var \App\Component\Setting\Setting
     */
    protected $setting;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @param \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     * @param \App\Model\Product\Availability\AvailabilityDataFactory $availabilityDataFactory
     * @param \App\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        AvailabilityFacade $availabilityFacade,
        AvailabilityDataFactory $availabilityDataFactory,
        Setting $setting,
        Domain $domain
    ) {
        $this->availabilityFacade = $availabilityFacade;
        $this->availabilityDataFactory = $availabilityDataFactory;
        $this->setting = $setting;
        $this->domain = $domain;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var \App\Model\Product\Availability\AvailabilityData $availabilityData */
        $availabilityData = $this->availabilityDataFactory->create();

        foreach ($this->domain->getAllLocales() as $locale) {
            $availabilityData->name[$locale] = t('Připravujeme', [], 'dataFixtures', $locale);
        }

        $availabilityData->dispatchTime = 14;
        $availabilityData->rgbColor = '#ff00ff';
        $this->createAvailability($availabilityData, self::AVAILABILITY_PREPARING);

        foreach ($this->domain->getAllLocales() as $locale) {
            $availabilityData->name[$locale] = t('Skladem v e-shopu', [], 'dataFixtures', $locale);
        }

        $availabilityData->dispatchTime = 0;
        $availabilityData->rgbColor = AvailabilityData::DEFAULT_COLOR;
        $inStockAvailability = $this->createAvailability($availabilityData, self::AVAILABILITY_IN_STOCK);
        $this->setting->set(Setting::DEFAULT_AVAILABILITY_IN_STOCK, $inStockAvailability->getId());

        foreach ($this->domain->getAllLocales() as $locale) {
            $availabilityData->name[$locale] = t('Na dotaz', [], 'dataFixtures', $locale);
        }

        $availabilityData->dispatchTime = 7;
        $availabilityData->rgbColor = '#666666';
        $this->createAvailability($availabilityData, self::AVAILABILITY_ON_REQUEST);

        $defaultOutOfStockAvailability = $this->availabilityFacade->getDefaultOutOfStockAvailability();
        $this->addReference(self::AVAILABILITY_OUT_OF_STOCK, $defaultOutOfStockAvailability);
    }

    /**
     * @param \App\Model\Product\Availability\AvailabilityData $availabilityData
     * @param string|null $referenceName
     * @return \App\Model\Product\Availability\Availability
     */
    protected function createAvailability(AvailabilityData $availabilityData, $referenceName = null)
    {
        /** @var \App\Model\Product\Availability\Availability $availability */
        $availability = $this->availabilityFacade->create($availabilityData);
        if ($referenceName !== null) {
            $this->addReference($referenceName, $availability);
        }

        return $availability;
    }
}

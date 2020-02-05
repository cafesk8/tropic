<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Product\Availability\AvailabilityData;
use App\Model\Product\Availability\AvailabilityDataFactory;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade;

class AvailabilityDataFixture extends AbstractReferenceFixture
{
    public const AVAILABILITY_IN_STOCK = 'availability_in_stock';
    public const AVAILABILITY_ON_REQUEST = 'availability_on_request';
    public const AVAILABILITY_OUT_OF_STOCK = 'availability_out_of_stock';
    public const AVAILABILITY_PREPARING = 'availability_preparing';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade
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
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     * @param \App\Model\Product\Availability\AvailabilityDataFactory $availabilityDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Setting\Setting $setting
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
        $availabilityData = $this->availabilityDataFactory->create();

        foreach ($this->domain->getAllLocales() as $locale) {
            $availabilityData->name[$locale] = t('Připravujeme', [], 'dataFixtures', $locale);
        }

        $availabilityData->dispatchTime = 14;
        $availabilityData->rgbColor = '#ff00ff';
        $this->createAvailability($availabilityData, self::AVAILABILITY_PREPARING);

        foreach ($this->domain->getAllLocales() as $locale) {
            $availabilityData->name[$locale] = t('Skladem', [], 'dataFixtures', $locale);
        }

        $availabilityData->dispatchTime = 0;
        $availabilityData->rgbColor = '#c0e314';
        $inStockAvailability = $this->createAvailability($availabilityData, self::AVAILABILITY_IN_STOCK);
        $this->setting->set(Setting::DEFAULT_AVAILABILITY_IN_STOCK, $inStockAvailability->getId());

        foreach ($this->domain->getAllLocales() as $locale) {
            $availabilityData->name[$locale] = t('Na dotaz', [], 'dataFixtures', $locale);
        }

        $availabilityData->dispatchTime = 7;
        $availabilityData->rgbColor = '#666666';
        $this->createAvailability($availabilityData, self::AVAILABILITY_ON_REQUEST);

        foreach ($this->domain->getAllLocales() as $locale) {
            $availabilityData->name[$locale] = t('Nedostupné', [], 'dataFixtures', $locale);
        }

        $availabilityData->dispatchTime = null;
        $availabilityData->rgbColor = '#ff0000';
        $this->createAvailability($availabilityData, self::AVAILABILITY_OUT_OF_STOCK);
    }

    /**
     * @param \App\Model\Product\Availability\AvailabilityData $availabilityData
     * @param string|null $referenceName
     * @return \Shopsys\FrameworkBundle\Model\Product\Availability\Availability
     */
    protected function createAvailability(AvailabilityData $availabilityData, $referenceName = null)
    {
        $availability = $this->availabilityFacade->create($availabilityData);
        if ($referenceName !== null) {
            $this->addReference($referenceName, $availability);
        }

        return $availability;
    }
}

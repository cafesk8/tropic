<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use App\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade as BaseAvailabilityFacade;

/**
 * @method \App\Model\Product\Availability\Availability getDefaultInStockAvailability()
 * @property \App\Component\Setting\Setting $setting
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityRepository $availabilityRepository, \App\Component\Setting\Setting $setting, \Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler $productAvailabilityRecalculationScheduler, \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFactoryInterface $availabilityFactory, \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher)
 * @method \App\Model\Product\Availability\Availability getById(int $availabilityId)
 * @method \App\Model\Product\Availability\Availability create(\App\Model\Product\Availability\AvailabilityData $availabilityData)
 * @method \App\Model\Product\Availability\Availability edit(int $availabilityId, \App\Model\Product\Availability\AvailabilityData $availabilityData)
 * @method setDefaultInStockAvailability(\App\Model\Product\Availability\Availability $availability)
 * @method \App\Model\Product\Availability\Availability[] getAll()
 * @method \App\Model\Product\Availability\Availability[] getAllExceptId(int $availabilityId)
 * @method bool isAvailabilityUsed(\App\Model\Product\Availability\Availability $availability)
 * @method bool isAvailabilityDefault(\App\Model\Product\Availability\Availability $availability)
 * @method dispatchAvailabilityEvent(\App\Model\Product\Availability\Availability $availability, string $eventType)
 */
class AvailabilityFacade extends BaseAvailabilityFacade
{
    /**
     * @return string[]
     */
    public function getColorsIndexedByName(): array
    {
        $colors = [];

        /** @var \App\Model\Product\Availability\Availability $availability */
        foreach ($this->getAll() as $availability) {
            $colors[$availability->getName()] = $availability->getRgbColor();
        }

        return $colors;
    }

    /**
     * @return \App\Model\Product\Availability\Availability
     */
    public function getDefaultOutOfStockAvailability(): Availability
    {
        $availabilityId = $this->setting->get(Setting::DEFAULT_AVAILABILITY_OUT_OF_STOCK_ID);
        /** @var \App\Model\Product\Availability\Availability $availability */
        $availability = $this->getById($availabilityId);

        return $availability;
    }
}

<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use Shopsys\FrameworkBundle\Model\Product\Availability\Availability as BaseAvailability;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityData as BaseAvailabilityData;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityDataFactory as BaseAvailabilityDataFactory;

/**
 * @method fillNew(\App\Model\Product\Availability\AvailabilityData $availabilityData)
 * @method fillFromAvailability(\App\Model\Product\Availability\AvailabilityData $availabilityData, \App\Model\Product\Availability\Availability $availability)
 */
class AvailabilityDataFactory extends BaseAvailabilityDataFactory
{
    /**
     * @return \App\Model\Product\Availability\AvailabilityData
     */
    public function create(): BaseAvailabilityData
    {
        $availabilityData = new AvailabilityData();
        $this->fillNew($availabilityData);

        return $availabilityData;
    }

    /**
     * @param \App\Model\Product\Availability\Availability $availability
     * @return \App\Model\Product\Availability\AvailabilityData
     */
    public function createFromAvailability(BaseAvailability $availability): BaseAvailabilityData
    {
        $availabilityData = new AvailabilityData();
        $this->fillFromAvailability($availabilityData, $availability);
        $availabilityData->rgbColor = $availability->getRgbColor();

        return $availabilityData;
    }
}

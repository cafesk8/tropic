<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use App\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade as BaseAvailabilityFacade;

/**
 * @method \App\Model\Product\Availability\Availability getDefaultInStockAvailability()
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

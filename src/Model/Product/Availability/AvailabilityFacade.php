<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade as BaseAvailabilityFacade;

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
}

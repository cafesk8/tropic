<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityGridFactory as BaseAvailabilityGridFactory;

class AvailabilityGridFactory extends BaseAvailabilityGridFactory
{
    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function create()
    {
        $grid = parent::create();
        $grid->addColumn('rgbColor', 'a.rgbColor', t('Colour'), true);

        return $grid;
    }
}

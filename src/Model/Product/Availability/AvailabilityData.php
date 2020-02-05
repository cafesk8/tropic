<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityData as BaseAvailabilityData;

class AvailabilityData extends BaseAvailabilityData
{
    /**
     * @var string
     */
    public $rgbColor;

    public function __construct()
    {
        parent::__construct();
        $this->rgbColor = '#c0e314';
    }
}
